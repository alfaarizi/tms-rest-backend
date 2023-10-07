<?php

namespace app\components;

use app\exceptions\CanvasRequestException;
use app\models\AccessToken;
use app\models\CodeCheckerResult;
use app\models\Group;
use app\models\InstructorGroup;
use app\models\StudentFile;
use app\models\Subscription;
use app\models\Task;
use app\models\User;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use yii\helpers\Json;
use yii\helpers\VarDumper;
use yii\httpclient\Client;
use yii\helpers\Console;
use ForceUTF8\Encoding;
use yii\httpclient\Exception;

/**
 *  This class implements the canvas synchronization methods.
 */
class CanvasIntegration
{
    private static array $scopes = [
        // Courses API
        'url:GET|/api/v1/courses', // List your courses
        //'url:GET|/api/v1/courses/:course_id/students', // List students (DEPRECATED)
        'url:GET|/api/v1/courses/:course_id/users', // List users in course
        'url:GET|/api/v1/courses/:id', // Get a single course
        'url:GET|/api/v1/courses/:course_id/users/:id', // Get single user
        // Groups API
        //'url:GET|/api/v1/courses/:course_id/groups', // List the groups available in a context
        //'url:GET|/api/v1/groups/:group_id', // Get a single group
        //'url:GET|/api/v1/groups/:group_id/users', // List group's users
        // Sections API
        'url:GET|/api/v1/courses/:course_id/sections', // List course sections
        'url:GET|/api/v1/courses/:course_id/sections/:id', // Get section information
        // Enrollments API
        'url:GET|/api/v1/courses/:course_id/enrollments', // List enrollments
        'url:GET|/api/v1/sections/:section_id/enrollments', // List enrollments
        'url:GET|/api/v1/users/:user_id/enrollments', // List enrollments
        // Assignments API
        'url:GET|/api/v1/courses/:course_id/assignments', // List assignments
        'url:GET|/api/v1/courses/:course_id/assignment_groups/:assignment_group_id/assignments', // List assignments
        'url:GET|/api/v1/courses/:course_id/assignments/:id', // Get a single assignment
        // Submissions API
        'url:GET|/api/v1/courses/:course_id/assignments/:assignment_id/submissions', // List assignment submissions
        'url:GET|/api/v1/sections/:section_id/assignments/:assignment_id/submissions', // List assignment submissions
        //'url:GET|/api/v1/courses/:course_id/assignments/:assignment_id/gradeable_students', // List gradeable students
        'url:GET|/api/v1/courses/:course_id/assignments/:assignment_id/submissions/:user_id', // Get a single submission
        'url:GET|/api/v1/sections/:section_id/assignments/:assignment_id/submissions/:user_id', // Get a single submission
        'url:PUT|/api/v1/courses/:course_id/assignments/:assignment_id/submissions/:user_id', // Grade or comment on a submission
        'url:PUT|/api/v1/sections/:section_id/assignments/:assignment_id/submissions/:user_id', // Grade or comment on a submission
        //'url:PUT|/api/v1/courses/:course_id/assignments/:assignment_id/submissions/:user_id/read', // Mark submission as read
        //'url:PUT|/api/v1/sections/:section_id/assignments/:assignment_id/submissions/:user_id/read', // Mark submission as read
        //'url:DELETE|/api/v1/courses/:course_id/assignments/:assignment_id/submissions/:user_id/read', // Mark submission as unread
        //'url:DELETE|/api/v1/sections/:section_id/assignments/:assignment_id/submissions/:user_id/read', // Mark submission as unread
        //'url:POST|/api/v1/courses/:course_id/assignments/:assignment_id/submissions', // Submit an assignment
        //'url:POST|/api/v1/sections/:section_id/assignments/:assignment_id/submissions', // Submit an assignment
        //'url:POST|/api/v1/courses/:course_id/assignments/:assignment_id/submissions/:user_id/files', // Upload a file
        //'url:POST|/api/v1/sections/:section_id/assignments/:assignment_id/submissions/:user_id/files', // Upload a file
    ];

    private array $syncErrorMsgs;

    public static function getLoginURL(): string
    {
        $scopes = implode(' ', self::$scopes);
        $currentToken = AccessToken::getCurrent();
        $currentToken->canvasOAuth2State = Yii::$app->getSecurity()->generateRandomString(10);
        $currentToken->save();

        return rtrim(Yii::$app->params['canvas']['url'], '/') . '/login/oauth2/auth' .
        '?client_id=' . Yii::$app->params['canvas']['clientID'] .
        '&response_type=code' .
        '&purpose=TMS-Canvas synchronization' .
        '&redirect_uri=' . Yii::$app->params['canvas']['redirectUri'] .
        '&state=' . $currentToken->canvasOAuth2State .
        "&scope=$scopes";
    }

    /**
     * Get the new canvas token from the canvas and save in the database.
     * @param User $user the actual user
     * @param int $timeLimit number of seconds before the token expiration while renewal is not required
     * @return bool true if refreshing the token was successful, otherwise false
     */
    public function refreshCanvasToken(User $user, int $timeLimit = 900): bool
    {
        if (strtotime($user->canvasTokenExpiry) > time() + $timeLimit) {
            return true;
        }

        $client = new Client(['baseUrl' => Yii::$app->params['canvas']['url']]);
        $response = $client->createRequest()
            ->setMethod('POST')
            ->setUrl('login/oauth2/token')
            ->setData(['grant_type' => 'refresh_token',
                'client_id' => Yii::$app->params['canvas']['clientID'],
                'client_secret' => Yii::$app->params['canvas']['secretKey'],
                'refresh_token' => $user->refreshToken])
            ->send();
        if ($response->isOk) {
            $responseJson = Json::decode($response->content);
            $user->canvasToken = $responseJson['access_token'];
            $user->canvasTokenExpiry = date('Y/m/d H:i:s', time() + intval($responseJson['expires_in']));
            $user->save();

            if (Yii::$app->id == 'tms-console' && $response->headers->has('X-Rate-Limit-Remaining')) {
                $rateLimit = round(floatval($response->headers->get('X-Rate-Limit-Remaining')), 2);
                $sleepTime = 0;
                if ($rateLimit < 100) {
                    $sleepTime = 20;
                } elseif ($rateLimit < 200) {
                    $sleepTime = 10;
                }

                Console::output("Rate limit is $rateLimit, sleep time is $sleepTime.");
                if ($sleepTime > 0) {
                    sleep($sleepTime);
                }
            }
        } else {
            Yii::warning("Failed to refresh Canvas token for user {$user->neptun} (ID: #{$user->id}).", __METHOD__);

            try {
                $responseJson = Json::decode($response->content);

                if (
                    $responseJson['error'] == 'invalid_request'
                    && $responseJson['error_description'] == 'refresh_token not found'
                ) {
                    $user->canvasToken = $user->refreshToken = null;
                    $user->save();
                    Yii::info("Deleting Canvas token for user {$user->neptun} (ID: #{$user->id}).", __METHOD__);
                } else {
                    Yii::error("Refreshing Canvas token for user {$user->neptun} (ID: #{$user->id}) failed." .
                           "Error: {$responseJson['error']}. Description: {$responseJson['error_description']}", __METHOD__);
                }
            } catch (InvalidArgumentException $e) {
                Yii::error("Refreshing Canvas token for user {$user->neptun} (ID: #{$user->id}) failed." .
                       "Error: {$response->content}", __METHOD__);
            }
        }
        return $response->isOk;
    }

    /**
     * Get the sections to the given course from the canvas
     * @param int $courseId the canvas id of the selected course
     * @return array the canvas sections response data in an array
     */
    public function findCanvasSections(int $courseId): array
    {
        $out = [];
        $user = User::findIdentity(Yii::$app->user->id);
        $client = new Client(['baseUrl' => Yii::$app->params['canvas']['url']]);

        $page = 1;
        $morePages = true;
        do {
            $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl(['api/v1/courses/' . $courseId . '/sections', 'include[]' => 'total_students'])
            ->setHeaders(['Authorization' => 'Bearer ' . $user->canvasToken])
            ->setData(
                [
                    'page' => $page++,
                    'per_page' => 50
                ]
            )
                ->send();
            if (!$response->isOk) {
                Yii::error("Fetching sections from Canvas failed for course #{$courseId}.", __METHOD__);
                throw new CanvasRequestException($response->statusCode, 'Fetching sections from Canvas failed.');
            }

            $out = array_merge($out, $response->data);
            $morePages = !empty($response->data);
        } while ($morePages);

        return $out;
    }

    /**
     * Get the all courses from the canvas
     * @return array the canvas courses response data in an array
     */
    public function findCanvasCourses(): array
    {
        $courses = [];
        $user = User::findIdentity(Yii::$app->user->id);
        $client = new Client(['baseUrl' => Yii::$app->params['canvas']['url']]);

        $page = 1;
        $morePages = true;
        do {
            $response = $client->createRequest()
                ->setMethod('GET')
                ->setUrl('api/v1/courses')
                ->setHeaders(['Authorization' => 'Bearer ' . $user->canvasToken])
                ->setData([
                    'enrollment_type' => 'teacher',
                    'include[]' => 'term',
                    'page' => $page++,
                    'per_page' => 50
                ])
                ->send();
            if (!$response->isOk) {
                Yii::error("Fetching courses from Canvas failed for user {$user->neptun} (ID: #{$user->id}).", __METHOD__);
                throw new CanvasRequestException($response->statusCode, 'Fetching courses from Canvas failed.');
            }

            $out = $response->data;
            $morePages = !empty($response->data);

            foreach ($out as $canvasCourse) {
                if (
                    empty($canvasCourse['term']) || empty($canvasCourse['term']['end_at']) ||
                    strtotime($canvasCourse['term']['end_at']) > time()
                ) {
                    array_push($courses, $canvasCourse);
                }
            }
        } while ($morePages);

        return $courses;
    }

    /**
     * Save the synchronizer id to the group and set the synchronized
     * @param int $tmsId the id of the group in the tms
     * @param int $canvasSectionId the id of the section in the canvas
     * @param int $canvasCourseId the id of the course in the canvas
     * @return Group the updated group
     */
    public function saveCanvasGroup(int $tmsId, int $canvasSectionId, int $canvasCourseId): Group
    {
        $group = Group::findOne($tmsId);
        $group->canvasSectionID = $canvasSectionId;
        $group->canvasCourseID = $canvasCourseId;
        $group->synchronizerID = Yii::$app->user->id;
        $group->save();

        Yii::info(
            "Canvas configuration has been saved for group" . PHP_EOL .
            "Course: {$group->course->name}, group number: {$group->number}, groupID: {$group->id}",
            __METHOD__
        );
        return $group;
    }

    /**
     * Get the given group data from canvas and save in the database
     * @param Group $group the selected group
     */
    public function synchronizeGroupData(Group $group): void
    {
        $this->syncErrorMsgs = array();
        if (!empty($group->canvasCourseID)) {
            $group->lastSyncTime = date('Y-m-d H:i:s');
            $group->save(); // Update last sync time, so in case of error the queue won't get stuck

            $this->saveCanvasTeachersToGroup($group);
            $this->saveTasksToCourse($group);
            $this->saveCanvasStudentsToGroup($group);
            $this->saveSubmissions($group);

            $syncErrorMsgsString = null;
            if (count($this->syncErrorMsgs) != 0) {
                $syncErrorMsgsString = implode(PHP_EOL, $this->syncErrorMsgs);
            } else {
                Yii::info(
                    "Canvas sync was successful" . PHP_EOL .
                    "Course: {$group->course->name}, group number: {$group->number}, groupID: {$group->id}",
                    __METHOD__
                );
            }

            if ($group->canvasErrors != $syncErrorMsgsString) {
                $updatedGroup = Group::find()->where(['id' => $group->id])->one();
                $updatedGroup->canvasErrors = $syncErrorMsgsString;
                $updatedGroup->save();
                if ($syncErrorMsgsString !== null) {
                    $this->sendEmailsAboutErrors($updatedGroup);
                }
            }
        }
    }

    /**
     * sending emails to the relevant instructors about the canvas errors, which occurred during canvas sync
     */
    private function sendEmailsAboutErrors(Group $group): void
    {
        $originalLanguage = Yii::$app->language;
        $instructors = $group->getInstructors()->all();
        foreach ($instructors as $instructor) {
            if (!empty($instructor->notificationEmail)) {
                Yii::$app->language = $instructor->locale;
                ;
                Yii::$app->mailer->compose(
                    'instructor/canvasErrors',
                    [
                        'group' => $group
                    ]
                )
                    ->setFrom(Yii::$app->params['systemEmail'])
                    ->setTo($instructor->notificationEmail)
                    ->setSubject(Yii::t('app/mail', 'Canvas synchronization errors'))
                    ->send();
            }
        }

        Yii::$app->language = $originalLanguage;
        Yii::info(
            'Emails were successfully sent about canvas synchronization errors. ' .
            "Group id: $group->id",
            __METHOD__
        );
    }

    /**
     * Get the students to the group from canvas and save in the database
     * @param Group $group the selected group
     */
    private function saveCanvasStudentsToGroup(Group $group): void
    {
        $client = new Client(['baseUrl' => Yii::$app->params['canvas']['url']]);
        $subscriptions = [];

        $page = 1;
        $morePages = true;
        do {
            //if the number is -1, get all users to the course
            if ($group->canvasSectionID == -1) {
                $response = $client->createRequest()
                    ->setMethod('GET')
                    ->setUrl('api/v1/courses/' . $group->canvasCourseID . '/enrollments')
                    ->setHeaders(['Authorization' => 'Bearer ' . $group->synchronizer->canvasToken])
                    ->setData([
                        'type[]' => 'StudentEnrollment',
                        'page' => $page++,
                        'per_page' => 50])
                    ->send();
            } else {
                $response = $client->createRequest()
                    ->setMethod('GET')
                    ->setUrl('api/v1/sections/' . $group->canvasSectionID . '/enrollments')
                    ->setHeaders(['Authorization' => 'Bearer ' . $group->synchronizer->canvasToken])
                    ->setData([
                        'type[]' => 'StudentEnrollment',
                        'page' => $page++,
                        'per_page' => 50
                      ])
                    ->send();
            }
            if (!$response->isOk) {
                $errorMsg = 'Fetching students from Canvas failed.';
                array_push($this->syncErrorMsgs, $errorMsg);
                Yii::error(
                    $errorMsg . PHP_EOL .
                    "Course: {$group->course->name}, group number: {$group->number}, groupID: {$group->id}",
                    __METHOD__
                );
                throw new CanvasRequestException($response->statusCode, $errorMsg);
            }

            $out = $response->data;
            $morePages = !empty($response->data);

            foreach ($out as $canvasEnrollment) {
                $user = $this->saveCanvasUser($canvasEnrollment['user']);
                if ($user !== null) {
                    $subscription = $this->getUserSubscription($user, $group->id);
                    if ($subscription === null) {
                        $subscription = $this->saveSubscription($user, $group);
                    }
                    if ($subscription !== null) {
                        array_push($subscriptions, $subscription);
                    }
                }
            }
        } while ($morePages);

        $condition = [
            'AND',
            ['NOT', ['id' => $subscriptions]],
            ['groupID' => $group->id]
        ];
        Subscription::deleteAll($condition);
    }

    /**
     * Get the teachers to the group from canvas and save in the database
     * @param Group $group the selected group
     */
    private function saveCanvasTeachersToGroup(Group $group): void
    {
        $client = new Client(['baseUrl' => Yii::$app->params['canvas']['url']]);
        $groups = [];

        $page = 1;
        $hasAny = false;
        $morePages = true;
        do {
            $response = $client->createRequest()
                ->setMethod('GET')
                ->setUrl($group->canvasSectionID == -1
                    ? 'api/v1/courses/' . $group->canvasCourseID . '/enrollments'
                    : 'api/v1/sections/' . $group->canvasSectionID . '/enrollments')
                ->setHeaders(['Authorization' => 'Bearer ' . $group->synchronizer->canvasToken])
                ->setData([
                    'type[]' => 'TeacherEnrollment',
                    'page' => $page++,
                    'per_page' => 50])
                ->send();
            if (!$response->isOk) {
                $errorMsg = 'Fetching teachers from Canvas failed.';
                array_push($this->syncErrorMsgs, $errorMsg);
                Yii::error(
                    $errorMsg . PHP_EOL .
                    "Course: {$group->course->name}, group number: {$group->number}, groupID: {$group->id}",
                    __METHOD__
                );
                throw new CanvasRequestException($response->statusCode, $errorMsg);
            }

            $out = $response->data;
            $morePages = !empty($response->data);

            if (!$hasAny && count($out) > 0) {
                $hasAny = true;
            }

            foreach ($out as $canvasEnrollment) {
                $instructor = $this->saveCanvasUser($canvasEnrollment['user']);
                if ($instructor !== null) {
                    $instructorGroup = InstructorGroup::find()->andWhere(['groupID' => $group->id])->andWhere(['userID' => $instructor->id])->one();
                    if (empty($instructorGroup)) {
                        $instructorGroup = $this->saveInstructorGroup($instructor->id, $group->id);
                    }
                    if ($instructorGroup !== null) {
                        array_push($groups, $instructorGroup->id);
                    }
                }
            }
        } while ($morePages);

        // Delete existing instructors only if there were any assigned to the Canvas course
        /*if ($hasAny) {
        $condition = [
            'AND',
            ['NOT', ['id' => $groups]],
            ['groupID' => $group->id]
        ];
        InstructorGroup::deleteAll($condition);
        }*/
    }

    /**
     * Save the canvas user in the database
     * @param array $canvasUser the user response from the canvas
     * @return User|null the updated user, or null if there was a database error
     */
    private function saveCanvasUser(array $canvasUser): ?User
    {
        $matches = null;
        if (preg_match("/^(.+) +\(([A-Za-z0-9]{6})\)$/", $canvasUser["name"], $matches)) {
            $name = $matches[1];
            $neptun = $matches[2];
        } else {
            $name = $canvasUser["name"];
            $neptun = strval($canvasUser["id"]);
        }
        $user = User::find()->orWhere(['canvasID' => $canvasUser["id"]])->orWhere(['neptun' => $neptun])->one();
        if (empty($user)) {
            $user = new User();
            $user->name = $name;
            $user->neptun = $neptun;
        } elseif (empty($user->name)) {
            $user->name = $name;
        }

        $user->canvasID = $canvasUser["id"];
        if (!$user->save()) {
            $errorMsg = "Saving or updating Canvas user with name '$name' and Neptun ID '$neptun' failed.";
            array_push($this->syncErrorMsgs, $errorMsg);
            Yii::error($errorMsg .
                "Message: " . VarDumper::dumpAsString($user->firstErrors), __METHOD__);
            return null;
        }
        return $user;
    }

    /**
     * Create and save the new subscription to the canvas user
     * @param User $user the given user
     * @param Group $group the given group
     * @return int|null the id of created/updated subscription, or null if there was a database error
     */
    private function saveSubscription(User $user, Group $group): ?int
    {
        $subscription = new Subscription(
            [
                'groupID' => $group->id,
                'semesterID' => $group->semesterID,
                'userID' => $user->id
            ]
        );

        $tasks = $group->getTasks()->all();
        foreach ($tasks as $task) {
            $studentFile = new StudentFile();
            $studentFile->taskID = $task->id;
            $studentFile->isAccepted = StudentFile::IS_ACCEPTED_NO_SUBMISSION;
            $studentFile->autoTesterStatus = StudentFile::AUTO_TESTER_STATUS_NOT_TESTED;
            $studentFile->uploaderID = $subscription->userID;
            $studentFile->name = null;
            $studentFile->grade = null;
            $studentFile->notes = "";
            $studentFile->uploadTime = null;
            $studentFile->isVersionControlled = $task->isVersionControlled;
            $studentFile->uploadCount = 0;
            $studentFile->verified = true;
            $studentFile->codeCheckerResultID = null;

            $studentFile->save();
        }

        if (!$subscription->save()) {
            $errorMsg = "Saving subscription for group #{$group->id}, semester #{$group->semesterID} and user #{$user->id} failed.";
            array_push($this->syncErrorMsgs, $errorMsg);
            Yii::error($errorMsg .
                "Message: " . VarDumper::dumpAsString($subscription->firstErrors), __METHOD__);
            return null;
        }
        return $subscription->id;
    }

    /**
     * Create and save the new InstructorGroup to the canvas user
     * @param int $userId id of the given user
     * @param int $groupId id of the given group
     * @return InstructorGroup|null the created InstructorGroup, or null if there was a database error
     */
    private function saveInstructorGroup(int $userId, int $groupId): ?InstructorGroup
    {
        $instructorGroup = new InstructorGroup([
            'groupID' => $groupId,
            'userID' => $userId
        ]);
        if (!$instructorGroup->save()) {
            $errorMsg = "Saving InstructorGroup for group #$groupId and user #$userId failed.";
            array_push($this->syncErrorMsgs, $errorMsg);
            Yii::error($errorMsg .
                "Message: " . VarDumper::dumpAsString($instructorGroup->firstErrors), __METHOD__);
            return null;
        }

        // Assign faculty role if necessary
        $authManager = Yii::$app->authManager;
        if (!$authManager->checkAccess($userId, 'faculty')) {
            $authManager->assign($authManager->getRole('faculty'), $userId);
        }

        return $instructorGroup;
    }

    /**
     * Get the all task to the given group from canvas and save in the database
     * @param Group $group the selected group
     */
    private function saveTasksToCourse(Group $group): void
    {
        $client = new Client(['baseUrl' => Yii::$app->params['canvas']['url']]);
        $taskIds = [];

        $page = 1;

        do {
            $response = $client->createRequest()
                ->setMethod('GET')
                ->setUrl('api/v1/courses/' . $group->canvasCourseID . '/assignments')
                ->setHeaders(['Authorization' => 'Bearer ' . $group->synchronizer->canvasToken])
                ->setData([
                    'include[]' => 'overrides',
                    'override_assignment_dates' => false,
                    'page' => $page++,
                    'per_page' => 50])
                ->send();
            if (!$response->isOk) {
                $errorMsg = 'Fetching assignments from Canvas failed.';
                array_push($this->syncErrorMsgs, $errorMsg);
                Yii::error(
                    $errorMsg . PHP_EOL .
                    "Course: {$group->course->name}, group number: {$group->number}, groupID: {$group->id}",
                    __METHOD__
                );
                throw new CanvasRequestException($response->statusCode, $errorMsg);
            }

            $out = $response->data;
            $morePages = !empty($response->data);

            foreach ($out as $assignment) {
                if ($assignment['published'] && !$assignment['is_quiz_assignment']) {
                    if ($group->canvasSectionID == -1 && !empty($assignment['lock_at'])) {
                        $id = $this->saveTask($assignment, $group);
                        if ($id !== null) {
                            array_push($taskIds, $id);
                        }
                    } elseif ($group->canvasSectionID > 0) {
                        $sectionOverride = null;
                        if (!empty($assignment['overrides'])) {
                            foreach ($assignment['overrides'] as $override) {
                                if (isset($override['course_section_id']) && $override['course_section_id'] == $group->canvasSectionID) {
                                    $sectionOverride = $override;
                                    break;
                                }
                            }
                        }

                        if (!is_null($sectionOverride)) {
                            $assignment['due_at'] = $sectionOverride['due_at'];
                            $assignment['unlock_at'] = $sectionOverride['unlock_at'];
                            $assignment['lock_at'] = $sectionOverride['lock_at'];
                        }

                        if (!empty($assignment['lock_at'])) {
                            $id = $this->saveTask($assignment, $group);
                            if ($id !== null) {
                                array_push($taskIds, $id);
                            }
                        }
                    }
                }
            }
        } while ($morePages);

        // delete tasks and submissions recursively
        $condition = ['AND',
        ['NOT', ['id' => $taskIds]],
        ['groupID' => $group->id]
        ];
        $tasksToRemove = Task::find()->where($condition)->all();
        foreach ($tasksToRemove as $task) {
            foreach ($task->studentFiles as $submission) {
                $submission->delete();
            }
            $task->delete();
            FileHelper::removeDirectory(Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/' . $task->id . '/');
        }
    }

    /**
     * Save the task from canvas
     * @param array $assignment the response from canvas with the task data
     * @param Group $group the selected group
     * @return int|null the id of created/updated task, or null if there was a database error
     */
    private function saveTask(array $assignment, Group $group): ?int
    {
        $task = $group->getTasks()->where(['canvasID' => $assignment['id']])->one();
        // Create new task if it was not synchronized before
        if (empty($task)) {
            $task = new Task([
                'canvasID' => $assignment['id'],
                'autoTest' => false
            ]);
        }
        $isNewTask = $task->isNewRecord;

        if (mb_strlen($assignment['name']) > Task::MAX_NAME_LENGTH) {
            $task->name = Encoding::toUTF8(mb_substr($assignment['name'], 0, Task::MAX_NAME_LENGTH - 3)) . '...';
        } else {
            $task->name = Encoding::toUTF8($assignment['name']);
        }

        $task->description = Encoding::toUTF8(strip_tags($assignment['description']));
        $task->semesterID = $group->semesterID;
        $task->groupID = $group->id;
        $task->createrID = $group->synchronizerID;
        if (!empty($assignment['due_at'])) {
            $task->softDeadline = date('Y-m-d H:i:s', strtotime($assignment['due_at']));
        }
        if (!empty($assignment['unlock_at'])) {
            $task->available = date('Y-m-d H:i:s', strtotime($assignment['unlock_at']));
        }
        $task->hardDeadline = date('Y-m-d H:i:s', strtotime($assignment['lock_at']));
        $task->category = "Canvas tasks";

        $transaction = Yii::$app->db->beginTransaction();
        try {
            try {
                if (!$task->save()) {
                    $errorMsg = "Saving task for Group #{$group->id} failed.";
                    array_push($this->syncErrorMsgs, $errorMsg);
                    Yii::error($errorMsg .
                        "Message: " . VarDumper::dumpAsString($task->firstErrors), __METHOD__);
                    return null;
                }
            } catch (\yii\db\Exception $ex) {
                $task->name = Encoding::fixUTF8($task->name);
                $task->description = Encoding::fixUTF8($task->description);
                $task->save();
            }

            if ($isNewTask) {
                // Create new StudentFile for everybody in the group
                foreach ($task->group->subscriptions as $subscription) {
                    $studentFile = new StudentFile();
                    $studentFile->taskID = $task->id;
                    $studentFile->isAccepted = StudentFile::IS_ACCEPTED_NO_SUBMISSION;
                    $studentFile->autoTesterStatus = StudentFile::AUTO_TESTER_STATUS_NOT_TESTED;
                    $studentFile->uploaderID = $subscription->userID;
                    $studentFile->name = null;
                    $studentFile->grade = null;
                    $studentFile->notes = "";
                    $studentFile->uploadTime = null;
                    $studentFile->isVersionControlled = $task->isVersionControlled;
                    $studentFile->uploadCount = 0;
                    $studentFile->verified = true;
                    $studentFile->codeCheckerResultID = null;

                    if ($studentFile->save()) {
                        Yii::info(
                            "A new blank solution has been uploaded for " .
                            "{$studentFile->task->name} ($studentFile->taskID)",
                            __METHOD__
                        );
                    } else {
                        $errorMsg = "Creating blank solution for user {$subscription->user->neptun} (ID: {$subscription->userID}) on Task #{$task->id} failed.";
                        array_push($this->syncErrorMsgs, $errorMsg);
                        Yii::error(
                            $errorMsg .
                             ($studentFile->hasErrors()
                                 ? "Message: " . VarDumper::dumpAsString($studentFile->errors)
                                 : ""),
                            __METHOD__
                        );

                        $transaction->rollBack();
                        break;
                    }
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return $task->id;
    }

    /**
     * Gets and persists the submissions of the given group from canvas
     * @param Group $group the given group
     */
    private function saveSubmissions(Group $group): void
    {
        $client = new Client(['baseUrl' => Yii::$app->params['canvas']['url']]);

        $countSyncProblems = 0;

        foreach ($group->tasks as $task) {
            $studentFileIds = [];

            $page = 1;

            do {
                //if the number is -1, get all submissions to the course
                if ($group->canvasSectionID == -1) {
                    $response = $client->createRequest()
                        ->setMethod('GET')
                        ->setUrl('api/v1/courses/' . $group->canvasCourseID . '/assignments/' . $task->canvasID . '/submissions')
                        ->setHeaders(['Authorization' => 'Bearer ' . $group->synchronizer->canvasToken])
                        ->setData([
                            'include[]' => 'submission_comments',
                            'page' => $page++,
                            'per_page' => 50
                        ])
                        ->send();
                } else {
                    $response = $client->createRequest()
                        ->setMethod('GET')
                        ->setUrl('api/v1/sections/' . $group->canvasSectionID . '/assignments/' . $task->canvasID . '/submissions')
                        ->setHeaders(['Authorization' => 'Bearer ' . $group->synchronizer->canvasToken])
                        ->setData([
                            'include[]' => 'submission_comments',
                            'page' => $page++,
                            'per_page' => 50
                        ])
                        ->send();
                }

                if (!$response->isOk) {
                    $errorMsg = 'Fetching submissions from Canvas failed.';
                    array_push($this->syncErrorMsgs, $errorMsg);
                    Yii::error(
                        $errorMsg . PHP_EOL .
                        "Course: {$group->course->name}, group number: {$group->number}, groupID: {$group->id}",
                        __METHOD__
                    );
                    throw new CanvasRequestException($response->statusCode, $errorMsg);
                }

                $out = $response->data;
                $morePages = !empty($response->data);

                foreach ($out as $submission) {
                    $tmsFile = $this->saveSubmission($submission, $task);
                    if ($tmsFile != null && $tmsFile->id > 0) {
                        array_push($studentFileIds, $tmsFile->id);
                        if ($tmsFile->isAccepted == StudentFile::IS_ACCEPTED_CORRUPTED) {
                            $countSyncProblems++;
                        }
                    }
                }
            } while ($morePages);

            // Remove old submissions not belonging to a current student of the group
            $condition = ['AND',
                ['NOT', ['id' => $studentFileIds]],
                ['taskID' => $task->id]
            ];
            $submissionsToRemove = StudentFile::find()->where($condition)->all();
            foreach ($submissionsToRemove as $sf) {
                $sf->delete();
            }
        }

        if ($countSyncProblems > 0) {
            throw new CanvasRequestException(500, Yii::t(
                'app',
                'Synchronization problem occurred due to corrupted submissions. {count} submission(s) was corrupted. The corrupted files were not synchronized.',
                [
                'count' => $countSyncProblems
                ]
            ));
        }
    }

    /**
     * Save the submission from canvas, and notifies users if there is/are corrupted files
     * @param array $submission the response from canvas with the solution data
     * @param Task $task the given task
     * @return StudentFile|null the created/updated student file, null if the user cannot be found or
     * there was a database error
     */
    private function saveSubmission(array $submission, Task $task): ?StudentFile
    {
        $canvasFile = null;
        if (
            isset($submission['attachments']) &&
            is_array($submission['attachments']) &&
            count($submission['attachments']) > 0
        ) {
            $canvasFile = end($submission['attachments']);
        }
        $user = User::findOne(['canvasID' => $submission['user_id']]);
        $hasNewUpload = false;
        $newFileCorrupted = false;

        if (is_null($user)) {
            // $user not exists in TMS, it is the Test account of the Canvas course
            return null;
        }

        // Load StudentFile by Canvas ID
        /** @var StudentFile $tmsFile */
        $tmsFile = $task->getStudentFiles()->where(['canvasID' => $submission['id']])->one();

        // For first sync, load StudentFile by uploader user ID
        if (is_null($tmsFile)) {
            /** @var StudentFile $tmsFile */
            $tmsFile = $task->getStudentFiles()->where(['uploaderID' => $user->id])->one();

            if (is_null($tmsFile)) {
                // Should not occur since there should be a 'No submission' record even for non-submitted solutions.
                Yii::error("Solution for user {$user->neptun} (ID: #{$user->id}) on Task #{$task->id} not found.", __METHOD__);
                return null;
            }

            $tmsFile->canvasID = $submission['id'];
        }

        // Check if there is a submission in Canvas by the student
        if (!is_null($canvasFile)) {
            // Canvas file upload by student is invalid or corrupted
            if ($canvasFile['size'] == 0) { // deliberately == 0, so it checks for null as well
                if (strtotime($tmsFile->uploadTime) !== strtotime($canvasFile['updated_at'])) {
                    $this->saveCanvasFile($task->id, $canvasFile['display_name'], Yii::$app->basePath . StudentFile::PATH_OF_CORRUPTED_FILE, $user->neptun);
                    $tmsFile->name = $canvasFile['display_name'];
                    $tmsFile->uploadTime = date('Y-m-d H:i:s', strtotime($canvasFile['updated_at']));
                    $tmsFile->isAccepted = StudentFile::IS_ACCEPTED_CORRUPTED;
                    $tmsFile->autoTesterStatus = StudentFile::AUTO_TESTER_STATUS_NOT_TESTED;
                    $tmsFile->codeCheckerResultID = null;
                    $newFileCorrupted = true;
                }
            } else {
                if (strtotime($tmsFile->uploadTime) !== strtotime($canvasFile['updated_at'])) {
                    $this->saveCanvasFile($task->id, $canvasFile['display_name'], $canvasFile['url'], $user->neptun);
                    $tmsFile->name = $canvasFile['display_name'];
                    $tmsFile->uploadTime = date('Y-m-d H:i:s', strtotime($canvasFile['updated_at']));
                    $tmsFile->isAccepted = StudentFile::IS_ACCEPTED_UPLOADED;
                    $tmsFile->autoTesterStatus = StudentFile::AUTO_TESTER_STATUS_NOT_TESTED;
                    $tmsFile->uploadCount++;
                    $tmsFile->codeCheckerResultID = null;
                    $hasNewUpload = true;
                }
            }
        }

        // Update grade and notes
        if (!empty($submission['grader_id'])) {
            $tmsFile->grade = filter_var($submission['score'], FILTER_VALIDATE_FLOAT) === false ? null : $submission['score'];
            $grader = User::findOne(['canvasID' => $submission['grader_id']]);
            $tmsFile->graderID = $grader->id ?? null;
            if (!empty($submission['submission_comments'])) {
                foreach (array_reverse($submission['submission_comments']) as $comment) {
                    if (
                        $comment['author_id'] == $submission['grader_id'] &&
                        strpos($comment['comment'], 'TMS auto') !== 0 &&
                        strpos($comment['comment'], 'A TMS auto') !== 0
                    ) {
                        $tmsFile->notes = Encoding::toUTF8($comment['comment']);
                        break;
                    }
                }
            }
        }

        try {
            if (!$tmsFile->save()) {
                $errorMsg = "Saving solution for user {$user->neptun} (ID: #{$user->id}) on Task #{$task->id} failed.";
                array_push($this->syncErrorMsgs, $errorMsg);
                Yii::error(
                    $errorMsg .
                    "Message: " . VarDumper::dumpAsString($tmsFile->firstErrors),
                    __METHOD__
                );
                return null;
            }
        } catch (\yii\db\Exception $ex) {
            $tmsFile->notes = Encoding::fixUTF8($tmsFile->notes);
            if (!$tmsFile->save()) {
                $errorMsg = "Saving solution for user {$user->neptun} (ID: #{$user->id}) on Task #{$task->id} failed.";
                array_push($this->syncErrorMsgs, $errorMsg);
                Yii::error(
                    $errorMsg .
                    "Message: " . VarDumper::dumpAsString($tmsFile->firstErrors),
                    __METHOD__
                );
                return null;
            }
        }

        if ($hasNewUpload) {
            Yii::info(
                "A new solution has been uploaded for " .
                "{$tmsFile->task->name} ($tmsFile->taskID)",
                __METHOD__
            );
        } elseif ($newFileCorrupted) {
            if (!empty($user->notificationEmail)) {
                $originalLanguage = Yii::$app->language;
                Yii::$app->language = $user->locale;
                Yii::$app->mailer->compose(
                    'student/corruptedSubmission',
                    [
                        'studentFile' => $tmsFile
                    ]
                )
                    ->setFrom(Yii::$app->params['systemEmail'])
                    ->setTo($user->notificationEmail)
                    ->setSubject(Yii::t('app/mail', 'Corrupted submission'))
                    ->send();

                Yii::$app->language = $originalLanguage;
            }
            Yii::warning(
                "A corrupted file was found at " .
                "{$tmsFile->task->name} ($tmsFile->taskID)",
                __METHOD__
            );
        }

        return $tmsFile;
    }

    /**
     * Save the new file from canvas
     * @param int $taskID the id of given task
     * @param string $name the name of the file
     * @param string $url the file download link
     * @param string $neptun the neptun of the uploader
     */
    private function saveCanvasFile(int $taskID, string $name, string $url, string $neptun): void
    {
        // Get the dest path.
        $path = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/'
            . $taskID . '/' . strtolower($neptun) . '/';

        $this->deleteCanvasFiles($taskID, $neptun);

        // Create new folder if not exists
        if (!file_exists($path)) {
            FileHelper::createDirectory($path, 0755, true);
        }

        // Save the new file
        $context = file_get_contents($url);
        file_put_contents($path . $name, $context);
    }

    /**
     * Delete saved files from given folder
     * @param int $taskID the id of given task
     * @param string $neptun Neptun ID of the uploader
     */
    private function deleteCanvasFiles(int $taskID, string $neptun): void
    {
        // Get the dest path.
        $path = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/'
            . $taskID . '/' . strtolower($neptun) . '/';

        // Delete files from given folder
        if (file_exists($path)) {
            array_map('unlink', array_filter((array) glob($path . "*")));
        }
    }

    /**
     * Get the given users subscription to group
     * @param User $user the selected user
     * @param int $groupId the id of selected group
     * @return int|null the subscription id or null if the user is not subscribed to the group
     */
    private function getUserSubscription(User $user, int $groupId): ?int
    {
        foreach ($user->subscriptions as $subscription) {
            if ($subscription->groupID == $groupId) {
                return $subscription->id;
            }
        }
        return null;
    }

    /**
     * Upload the grade to canvas
     * @param int $studentFileId the id of graded student file
     */
    public function uploadGradeToCanvas(int $studentFileId): void
    {
        $user = User::findIdentity(Yii::$app->user->id);
        $studentFile = StudentFile::findOne($studentFileId);

        $client = new Client(['baseUrl' => Yii::$app->params['canvas']['url']]);
        $url = 'api/v1/courses/' . $studentFile->task->group->canvasCourseID .
            '/assignments/' . $studentFile->task->canvasID . '/submissions/' . $studentFile->uploader->canvasID;
        $client->createRequest()
            ->setMethod('PUT')
            ->setHeaders(['Authorization' => 'Bearer ' . $user->canvasToken])
            ->setUrl($url)
            ->setData([
                          'submission[posted_grade]' => is_null($studentFile->grade) ? "" : $studentFile->grade,
                          'comment[text_comment]' => $studentFile->notes])
            ->send();
    }

    /**
     * Upload the automatic tester result message to canvas
     * @param StudentFile $studentFile the tested student file
     */
    public function uploadTestResultToCanvas(StudentFile $studentFile): void
    {
        $synchronizer = $studentFile->task->group->synchronizer;
        if (is_null($synchronizer) || is_null($synchronizer->canvasToken)) {
            Yii::error(
                "Group #{$studentFile->task->groupID} has no valid Canvas synchronizer.",
                __METHOD__
            );
            return;
        }

        if (!empty($studentFile->safeErrorMsg) && $this->refreshCanvasToken($synchronizer)) {
            $originalLanguage = Yii::$app->language;
            Yii::$app->language = $studentFile->uploader->locale;

            $client = new Client(['baseUrl' => Yii::$app->params['canvas']['url']]);
            $url = 'api/v1/courses/' . $studentFile->task->group->canvasCourseID .
                '/assignments/' . $studentFile->task->canvasID . '/submissions/' . $studentFile->uploader->canvasID;
            $client->createRequest()
                ->setMethod('PUT')
                ->setHeaders(['Authorization' => 'Bearer ' . $synchronizer->canvasToken])
                ->setUrl($url)
                ->setData([
                              'comment[text_comment]' => Yii::t(
                                  'app',
                                  'TMS automatic tester result: '
                              ) . $studentFile->safeErrorMsg
                          ])
                ->send();

            Yii::$app->language = $originalLanguage;
        }
    }

    /**
     * Uploads a short summary of the CodeChecker result to Canvas
     * @throws \UnexpectedValueException Thrown if the CodeChecker result status of the student file has an unexpected value
     * @throws CanvasRequestException Thrown if Canvas returns with a status code that indicates error
     * @throws InvalidConfigException Thrown invalid configuration provided for the http client
     * @throws Exception Thrown if failed to send request to Canvas
     */
    public function uploadCodeCheckerResultToCanvas(StudentFile $studentFile): void
    {
        $synchronizer = $studentFile->task->group->synchronizer;
        if (is_null($synchronizer) || is_null($synchronizer->canvasToken)) {
            Yii::error(
                "Group #{$studentFile->task->groupID} has no valid Canvas synchronizer.",
                __METHOD__
            );
            return;
        }

        if (!empty($studentFile->codeCheckerResultID) && $this->refreshCanvasToken($synchronizer)) {
            $codeCheckerResult = $studentFile->codeCheckerResult;
            $comment = Yii::t('app', 'TMS static code analyzer result: ');
            switch ($codeCheckerResult->status) {
                case CodeCheckerResult::STATUS_NO_ISSUES:
                    $comment .= Yii::t('app', 'No issues were found in the uploaded submission.');
                    break;
                case CodeCheckerResult::STATUS_ISSUES_FOUND:
                    $comment .= Yii::t(
                        'app',
                        '{count} issue(s) were found in the uploaded submission. Visit TMS ({url}) for more information.',
                        [
                            'count' => count($codeCheckerResult->codeCheckerReports),
                            'url' => Yii::$app->params['frontendUrl']
                        ]
                    );
                    break;
                case CodeCheckerResult::STATUS_ANALYSIS_FAILED:
                    $comment .= Yii::t('app', 'Analysis Failed');
                    break;
                case CodeCheckerResult::STATUS_RUNNER_ERROR:
                    $comment .= Yii::t('app', 'Runner Error');
                    break;
                default:
                    throw new \UnexpectedValueException("Invalid CodeChecker result status for {$studentFile->id}");
            }

            $client = new Client(['baseUrl' => Yii::$app->params['canvas']['url']]);
            $url = 'api/v1/courses/' . $studentFile->task->group->canvasCourseID .
                '/assignments/' . $studentFile->task->canvasID . '/submissions/' . $studentFile->uploader->canvasID;
            $response = $client->createRequest()
                ->setMethod('PUT')
                ->setHeaders(['Authorization' => 'Bearer ' . $synchronizer->canvasToken])
                ->setUrl($url)
                ->setData(['comment[text_comment]' => $comment])
                ->send();

            if (!$response->isOk) {
                Yii::error(
                    'Saving CodeChecker results to Canvas failed' . PHP_EOL .
                    "Student File ID: {$studentFile->id}",
                    __METHOD__
                );
                throw new CanvasRequestException($response->statusCode, 'Failed to save CodeChecker results to canvas.');
            }
        }
    }
}
