<?php

namespace app\modules\instructor\controllers;

use app\components\AssignmentTester;
use app\components\GitManager;
use app\models\Group;
use app\models\InstructorFile;
use app\models\StudentFile;
use app\models\Subscription;
use app\models\Task;
use app\models\TestCase;
use app\modules\instructor\resources\GroupResource;
use app\modules\instructor\resources\SetupAutoTester;
use app\modules\instructor\resources\TaskResource;
use app\resources\SemesterResource;
use app\resources\UserResource;
use Yii;
use yii\base\ErrorException;
use yii\data\ActiveDataProvider;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;

/**
 * This class provides access to tasks for instructors
 */
class TasksController extends BaseInstructorRestController
{
    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return array_merge(
            parent::verbs(),
            [
                'index' => ['GET'],
                'view' => ['GET'],
                'create' => ['POST'],
                'delete' => ['DELETE'],
                'update' => ['PATCH', 'PUT'],
                'list-for-course' => ['GET'],
                'list-for-users' => ['POST'],
                'toggle-auto-tester' => ['PATCH'],
                'setup-auto-tester' => ['POST'],
                'tester-form-data' => ['GET']
            ]
        );
    }

    /**
     * @param int $groupID
     * @return ActiveDataProvider[]
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionIndex($groupID)
    {
        $group = GroupResource::findOne($groupID);

        if (is_null($group)) {
            throw new NotFoundHttpException('Group not found');
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        $categories = TaskResource::listCategories($group);

        $dataProviders = [];
        foreach ($categories as $category) {
            $query = $group
                ->getTasks()
                ->andWhere(['category' => $category]);

            $dataProviders[] = new ActiveDataProvider(
                [
                    'query' => $query,
                    'pagination' => false
                ]
            );
        }
        return $dataProviders;
    }

    public function actionView($id)
    {
        $task = TaskResource::findOne($id);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        return $task;
    }

    /**
     * @return TaskResource|array
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @throws BadRequestHttpException
     */
    public function actionCreate()
    {
        $task = new TaskResource();
        $task->scenario = TaskResource::SCENARIO_CREATE;
        $task->load(Yii::$app->request->post(), '');
        $task->createrID = Yii::$app->user->id;

        if (!$task->validate()) {
            $this->response->statusCode = 422;
            return $task->errors;
        }

        if ($task->isVersionControlled && !Yii::$app->params['versionControl']['enabled']) {
            throw new BadRequestHttpException(Yii::t('app', 'Version control is disabled. Contact the administrator for more information.'));
        }

        $task->semesterID = $task->group->semesterID;

        // Check semester
        if ($task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a group from a previous semester!")
            );
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        if (!$task->save(false)) {
            throw new ServerErrorHttpException(
                Yii::t('app', 'Failed to save task. Message: ') . Yii::t('app', 'A database error occurred')
            );
        }

        // Create remote repository for everybody in the group if the task is version controlled
        if (Yii::$app->params['versionControl']['enabled'] && $task->isVersionControlled) {
            foreach ($task->group->subscriptions as $subcription) {
                //$this->createRepositories($task->id, $subcription->user, $task->hardDeadline);
                GitManager::createRepositories($task->id, $subcription->user, $task->hardDeadline);
            }
        }

        // Email notifications
        $messages = [];

        $originalLanguage = Yii::$app->language;
        foreach ($task->group->subscriptions as $subscription) {
            if (!empty($subscription->user->email)) {
                Yii::$app->language = $subscription->user->locale;
                $messages[] = Yii::$app->mailer->compose(
                    'student/newTask',
                    [
                        'task' => $task,
                        'actor' => Yii::$app->user->identity,
                    ]
                )
                    ->setFrom(Yii::$app->params['systemEmail'])
                    ->setTo($subscription->user->email)
                    ->setSubject(Yii::t('app/mail', 'New task'));
            }
        }
        Yii::$app->language = $originalLanguage;

        // Send mass email notifications
        Yii::$app->mailer->sendMultiple($messages);

        Yii::info(
            "A new task $task->name (id: $task->id) has been created " .
            "for {$task->group->course->name} ({$task->group->number})",
            __METHOD__
        );

        $this->response->statusCode = 201;
        return $task;
    }

    /**
     * @param int $id
     * @return TaskResource|array
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     */
    public function actionUpdate($id)
    {
        // Get the task.
        $task = TaskResource::findOne($id);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }
        $task->scenario = TaskResource::SCENARIO_UPDATE;


        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        // Check semester
        if ($task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        // Canvas synchronization check
        if ($task->group->isCanvasCourse) {
            throw new BadRequestHttpException(
                Yii::t('app', 'This operation cannot be performed on a canvas synchronized course!')
            );
        }

        $oldAvailable = $task->available;
        $oldSoftDeadLine = $task->softDeadline;
        $oldHardDeadLine = $task->hardDeadline;
        $task->load(Yii::$app->request->post(), '');

        if (!$task->validate()) {
            $this->response->statusCode = 422;
            return $task->errors;
        }

        if (!$task->save(false)) {
            throw new ServerErrorHttpException(
                Yii::t('app', 'Failed to save task. Message: ') . Yii::t('app', 'A database error occurred')
            );
        }

        // Email notifications if deadline changed
        if (
            $task->available != $oldAvailable ||
            $task->softDeadline != $oldSoftDeadLine ||
            $task->hardDeadline != $oldHardDeadLine
        ) {
            $messages = [];
            $group = GroupResource::findOne($task->groupID);

            $originalLanguage = Yii::$app->language;
            foreach ($task->group->subscriptions as $subscription) {
                if (!empty($subscription->user->email)) {
                    Yii::$app->language = $subscription->user->locale;
                    $messages[] = Yii::$app->mailer->compose(
                        'student/updateTaskDeadline',
                        [
                            'task' => $task,
                            'actor' => Yii::$app->user->identity,
                            'group' => $group,
                        ]
                    )
                        ->setFrom(Yii::$app->params['systemEmail'])
                        ->setTo($subscription->user->email)
                        ->setSubject(Yii::t('app/mail', 'Task deadline change'));
                }

                // Change the hard deadline in the repository git hook as well for version controlled tasks
                if (Yii::$app->params['versionControl']['enabled'] && $task->isVersionControlled) {
                    GitManager::afterTaskUpdate($task, $subscription);
                }
            }
            Yii::$app->language = $originalLanguage;

            // Send mass email notifications
            Yii::$app->mailer->sendMultiple($messages);
        }

        Yii::info(
            "A task has been updated: $task->name (id: $task->id)." . PHP_EOL .
            "Course and group: {$task->group->course->name} ({$task->group->number})",
            __METHOD__
        );

        return $task;
    }

    /**
     * @param int $id
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        // Fetch the entities.
        $task = TaskResource::findOne($id);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        // Canvas synchronization check
        if ($task->group->isCanvasCourse) {
            throw new BadRequestHttpException(
                Yii::t('app', 'This operation cannot be performed on a canvas synchronized course!')
            );
        }

        // Check semester
        if ($task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        $instructorFiles = InstructorFile::findAll(['taskID' => $task->id]);
        $studentFiles = StudentFile::findAll(['taskID' => $task->id]);
        $testCases = TestCase::findAll(['taskID' => $task->id]);

        // Check for student solutions
        if (count($studentFiles) == 0) {
            // Try to delete them.
            try {
                // Delete instructor files.
                foreach ($instructorFiles as $file) {
                    // Delete the entry and the file from the disk.
                    $file->delete();
                }
                // Delete test cases
                foreach ($testCases as $case) {
                    // Delete the entity.
                    $case->delete();
                }

                if ($task->delete()) {
                    $this->response->statusCode = 204;
                    return;
                } else {
                    throw new ServerErrorHttpException(
                        Yii::t('app', 'Failed to delete task. Message: ') . Yii::t('app', "Database errors")
                    );
                }
            } catch (yii\db\IntegrityException $e) {
                throw new BadRequestHttpException(
                    Yii::t(
                        'app',
                        'Failed to remove task. This task can not be removed anymore, there is uploaded solution!'
                    )
                );
            } catch (ErrorException $e) {
                throw new ServerErrorHttpException(
                    Yii::t('app', 'Failed to delete task. Message: ') . $e->getMessage()
                );
            }
        } else {
            throw new BadRequestHttpException(
                Yii::t(
                    'app',
                    'Failed to remove task. This task can not be removed anymore, there is uploaded solution!'
                )
            );
        }
    }

    /**
     * This action is mainly used in plagiarism check form
     * @param int|string $courseID
     * @param mixed $myTasks
     * @param int $semesterFromID
     * @param int $semesterToID
     * @return ActiveDataProvider
     */
    public function actionListForCourse($courseID, $myTasks, $semesterFromID, $semesterToID)
    {
        $myTasks = filter_var($myTasks, FILTER_VALIDATE_BOOLEAN);
        $groupQuery = Group::find();
        if ($courseID != 'All') {
            $groupQuery = $groupQuery->where(['courseID' => $courseID]);
        }
        if ($myTasks) {
            $groupQuery = $groupQuery->joinWith('instructorGroups')->andWhere(
                ['userID' => $userID = Yii::$app->user->id]
            );
        }

        $instIds = array_map(
            function ($o) {
                return $o->id;
            },
            $groupQuery->all()
        );

        if ($semesterToID === $semesterFromID) {
            $semester = ['semesterID' => $semesterToID];
        } else {
            $semester = [
                'between',
                'semesterID',
                $semesterFromID,
                $semesterToID
            ];
        }

        $taskQuery = TaskResource::find()
            ->where(['groupID' => $instIds])
            ->andWhere($semester);

        return new ActiveDataProvider(
            [
                'query' => $taskQuery,
                'pagination' => false
            ]
        );
    }

    /**
     * @return array
     */
    public function actionListUsers()
    {
        $values = Yii::$app->request->post('ids', []);
        $studentsMap = [];

        // Process each task by there id.
        foreach ($values as $id) {
            $groupID = Task::findOne($id)->groupID;

            // Combine all the found user, filter unique users
            foreach (Subscription::findAll(['groupID' => $groupID]) as $subscription) {
                $user = $subscription->user;
                $studentsMap[$user->id] = new UserResource($user);
            }
        }

        // Convert map to array
        $studentsList = [];
        foreach ($studentsMap as $student) {
            $studentsList[] = $student;
        }

        return $studentsList;
    }

    public function actionToggleAutoTester($id)
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            throw new BadRequestHttpException(Yii::t('app', 'Auto tester is disabled. Contact the administrator for more information.'));
        }

        $task = TaskResource::findOne($id);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        // Check semester
        if ($task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        $task->autoTest = $task->autoTest === 0 ? 1 : 0;
        if (!$task->save()) {
            throw new ServerErrorHttpException(
                Yii::t('app', 'Failed to save task. Message: ') . Yii::t('app', 'A database error occurred')
            );
        }
        return $task;
    }

    /**
     * Updates auto tester for a task.
     *
     * @param int $id the id of the task
     * @return TaskResource|array
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws ErrorException
     *
     */
    public function actionSetupAutoTester($id)
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            throw new BadRequestHttpException(Yii::t('app', 'Auto tester is disabled. Contact the administrator for more information.'));
        }

        $task = TaskResource::findOne($id);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        // Check semester
        if ($task->semesterID !== SemesterResource::getActualID()) {
            throw new BadRequestHttpException(
                Yii::t('app', "You can't modify a task from a previous semester!")
            );
        }

        $setupData = new SetupAutoTester();
        $setupData->load(Yii::$app->request->post(), '');
        $setupData->files = UploadedFile::getInstancesByName('files');

        if (!$setupData->validate()) {
            $this->response->statusCode = 422;
            return $setupData->errors;
        }

        $task->testOS = $setupData->testOS;
        $task->imageName = $setupData->imageName;
        $task->compileInstructions = $setupData->compileInstructions;
        $task->runInstructions = $setupData->runInstructions;
        $task->showFullErrorMsg = $setupData->showFullErrorMsg;

        $sourcedir = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] .
            '/tmp/instructor/' . $task->groupID . '/' . $task->id . '/autotest/';

        // Create tmp dir for the given groupID and taskID
        if (file_exists($sourcedir)) {
            FileHelper::removeDirectory($sourcedir);
        }
        mkdir($sourcedir, 0755, true);

        if ($setupData->files) {
            foreach ($setupData->files as $file) {
                if (!$file->saveAs($sourcedir . $file->name)) {
                    // Log
                    Yii::error(
                        "Failed to save file to the disc ($file->name), error code: $file->error",
                        __METHOD__
                    );
                    throw new ServerErrorHttpException(Yii::t("app", "Failed to save file. Error logged.") . " ($file->name)");
                }
            }
        }

        if (file_exists($sourcedir . 'Dockerfile')) {
           if (AssignmentTester::alreadyBuilt($task->localImageName, Yii::$app->params['evaluator'][$task->testOS])) {
                AssignmentTester::removeImage($task->localImageName, Yii::$app->params['evaluator'][$task->testOS]);
            }

            $buildResult = AssignmentTester::buildImageForTask(
                $task->localImageName,
                $sourcedir,
                Yii::$app->params['evaluator'][$task->testOS]
            );

            if (!$buildResult['success']) {
                $error = $buildResult['log'] . PHP_EOL . $buildResult['error'];
                throw new ServerErrorHttpException($error);
            } else {
                $task->imageName = $task->localImageName;
            }
        }

        if (
            $task->localImageName != $task->imageName &&
            !AssignmentTester::alreadyBuilt($task->imageName, Yii::$app->params['evaluator'][$task->testOS])
        ) {
            AssignmentTester::pullImage($task->imageName, Yii::$app->params['evaluator'][$task->testOS]);
        }

        // Clean temp files
        if (file_exists($sourcedir)) {
            FileHelper::removeDirectory($sourcedir);
        }

        if ($task->save(false)) {
            return $task;
        } else {
            throw new ServerErrorHttpException(Yii::t('app', 'A database error occurred'));
        }
    }

    /**
     * Provides data for autotester setup
     * @param int $id
     * @return array
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionTesterFormData($id)
    {
        if (!Yii::$app->params['evaluator']['enabled']) {
            throw new BadRequestHttpException(Yii::t('app', 'Auto tester is disabled. Contact the administrator for more information.'));
        }

        $task = TaskResource::findOne($id);

        if (is_null($task)) {
            throw new NotFoundHttpException(Yii::t('app', 'Task not found.'));
        }

        // Authorization check
        if (!Yii::$app->user->can('manageGroup', ['groupID' => $task->groupID])) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'You must be an instructor of the group to perform this action!')
            );
        }

        $templates = [];
        $osMap = $task->testOSMap();

        foreach (Yii::$app->params['evaluator']['templates'] as $key => $template) {
            if (in_array($template['os'], array_keys($osMap))) {
                $templates[] = $template;
            }
        }

        return [
            'templates' => $templates,
            'osMap' => $osMap,
            'imageSuccessfullyBuilt' => AssignmentTester::alreadyBuilt($task->imageName, Yii::$app->params['evaluator'][$task->testOS])
        ];
    }
}
