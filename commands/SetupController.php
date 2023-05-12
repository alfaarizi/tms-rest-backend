<?php

namespace app\commands;

use app\models\InstructorGroup;
use app\models\Semester;
use app\models\Course;
use app\models\Group;
use app\models\StudentFile;
use app\models\Task;
use app\models\User;
use app\models\ExamQuestion;
use app\models\ExamQuestionSet;
use app\models\ExamAnswer;
use app\models\ExamTest;
use Yii;
use yii\console\ExitCode;
use yii\db\Exception;
use yii\helpers\Console;
use yii\helpers\FileHelper;
use app\models\Subscription;

/**
 * Manages application setup.
 */
class SetupController extends BaseController
{
    /**
     * Seeds the database with basic initial data.
     *
     * @return int Error code.
     */
    public function actionInit(): int
    {
        // Check if database is empty
        if (Semester::find()->count() > 0) {
            $this->stdout("Database is not empty and should be pruned before running this command. This can be done with the setup/prune command." . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::DATAERR;
        }

        // Seed Semester
        $month = intval(date('n'));

        // August -> December: fall semester
        if ($month >= 8) {
            $default_name = sprintf(
                '%s/%s/1',
                date('Y'),
                date('y', strtotime('+1 year'))
            );
        } elseif ($month == 1) {
            // January: fall semester
            $default_name = sprintf(
                '%s/%s/1',
                date('Y', strtotime('-1 year')),
                date('y')
            );
        } else {
            // February -> July: spring semester
            $default_name = sprintf(
                '%s/%s/2',
                date('Y', strtotime('-1 year')),
                date('y')
            );
        }

        if ($this->interactive) {
            $name = Console::prompt("Initial semester:", [
                'required' => true,
                'default' => $default_name,
                'pattern' => '|^\d{4}/\d{2}/[1,2]$|',
                'error' => 'Invalid semester format.',
            ]);
        } else {
            $name = $default_name;
        }

        $semester = new Semester();
        $semester->id = 1;
        $semester->name = $name;
        $semester->actual = true;
        if ($semester->save()) {
            $this->stdout("Successfully inserted initial semester '$name'." . PHP_EOL, Console::FG_GREEN);
        } else {
            $this->stdout("Failed to insert initial semester '$name'." . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        //Seed Admin
        $authManager = \Yii::$app->authManager;

        $default_name = 'administrator01';
        $default_neptun = 'admr01';

        if ($this->interactive) {
            $name = Console::prompt("Administrator name:", [
                'required' => true,
                'default' => $default_name,
            ]);
            $neptun = Console::prompt("Administrator identifier:", [
                'required' => true,
                'default' => $default_neptun,
            ]);
        } else {
            $name = $default_name;
            $neptun = $default_neptun;
        }

        $administrator = new User();
        $administrator->id = 1;
        $administrator->neptun = $neptun;
        $administrator->name = $name;
        if ($administrator->save()) {
            $authManager->assign($authManager->getRole('admin'), $administrator->id);
            $this->stdout("Successfully inserted administrator with identifier: '$administrator->neptun'." . PHP_EOL, Console::FG_GREEN);
        } else {
            $this->stdout("Failed to insert administrator." . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }


    /**
     * Seed one submission with given parameters.
     *
     * @throws Exception
     */
    private function seedSubmission(int $uploaderID, string $neptun, int $taskID, string $isAccepted, ?int $graderID, string $autoTesterStatus): void
    {
        $submission = new StudentFile();
        $submission->name = $neptun . ".zip";
        $submission->uploadTime = date('Y-m-d H:i:s');
        $submission->taskID = $taskID;
        $submission->uploaderID = $uploaderID;
        $submission->isAccepted = $isAccepted;
        $submission->autoTesterStatus = $autoTesterStatus;
        $submission->verified = 1;
        $submission->uploadCount = 1;
        $submission->grade = null;
        $submission->graderID = $graderID;
        $submission->notes = '';
        if ($submission->save()) {
            $this->stdout("Successfully inserted submission #$submission->id." . PHP_EOL, Console::FG_GREEN);
        } else {
            throw new Exception('Failed to insert submission.');
        }
    }

    /**
     * Seeds the database with full sample data.
     *
     * @return int Error code.
     * @throws \Exception
     */
    public function actionSample(): int
    {
        // Seed Semester and Admin
        $initExitCode = $this->actionInit();
        if ($initExitCode !== 0) {
            return $initExitCode;
        }

        //Seed Instructors
        $authManager = \Yii::$app->authManager;
        for ($i = 1; $i < 4; $i++) {
            $instructor = new User();
            $instructor->id = $i + 1;
            $instructor->neptun = 'inst0' . $i;
            $instructor->name = 'instructor0' . $i;
            $instructor->email = 'instructor0' . $i . '@example.com';
            if ($instructor->save()) {
                $authManager->assign($authManager->getRole('faculty'), $instructor->id);
                $this->stdout("Successfully inserted instructor with neptun '$instructor->neptun'." . PHP_EOL, Console::FG_GREEN);
            } else {
                $this->stdout("Failed to insert instructor with neptun '$instructor->neptun'." . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        // Seed Students
        for ($i = 1; $i < 7; $i++) {
            $student = new User();
            $student->id = $i + 4;
            $student->neptun = 'stud0' . $i;
            $student->name = 'student0' . $i;
            $student->email = 'student0' . $i . '@example.com';
            if ($student->save()) {
                $authManager->assign($authManager->getRole('student'), $student->id);
                $this->stdout("Successfully inserted student with neptun '$student->neptun'." . PHP_EOL, Console::FG_GREEN);
            } else {
                $this->stdout("Failed to insert student with neptun '$student->neptun'." . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        // Seed Course
        $name = 'Development of web based applications';
        $code = 'IP-08bWAFEG';

        $course = new Course();
        $course->id = 1;
        $course->name = $name;
        $course->code = $code;
        if ($course->save()) {
            $this->stdout("Successfully inserted initial course '$name'." . PHP_EOL, Console::FG_GREEN);
        } else {
            $this->stdout("Failed to insert initial course '$name'." . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Seed for exam module
        // Seed Groups
        $transaction = \Yii::$app->db->beginTransaction();
        $group = new Group();
        $group->id = 1;
        $group->courseID = 1;
        $group->semesterID = 1;
        $group->number = 1;
        $group->timezone = \Yii::$app->timeZone;

        if ($group->save()) {
            $this->stdout("Successfully inserted initial group #$group->number." . PHP_EOL, Console::FG_GREEN);
        } else {
            $this->stdout("Failed to insert initial group #$group->number." . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        for ($i = 1; $i < 4; $i++) {
            $instructorGroup = new InstructorGroup();
            $instructorGroup->userID = $i + 1;
            $instructorGroup->groupID = 1;

            if ($instructorGroup->save()) {
                $this->stdout("Successfully inserted initial group instructor for '{$instructorGroup->user->neptun}'." . PHP_EOL, Console::FG_GREEN);
            } else {
                $this->stdout("Failed to insert initial group instructor permission for '{$instructorGroup->user->neptun}'." . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        $transaction->commit();

        // Seed Subscriptions
        for ($i = 1; $i < 7; $i++) {
            $subscription = new Subscription();
            $subscription->userID = $i + 4;
            $subscription->groupID = 1;
            $subscription->semesterID = 1;
            $subscription->isAccepted = 1;
            $subscription->notes = 'Notes';
            if ($subscription->save()) {
                $this->stdout("Successfully inserted initial student course subscription for '{$subscription->user->neptun}'." . PHP_EOL, Console::FG_GREEN);
            } else {
                $this->stdout("Failed to insert initial student course subscription for '{$subscription->user->neptun}'." . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        // Seed Tasks
        for ($i = 1; $i < 3; $i++) {
            $task = new Task();
            $task->id = $i;
            $task->name = "Task $i";
            $task->semesterID = 1;
            $task->groupID = 1;
            $task->hardDeadline = date('Y-m-d H:i:s', strtotime("+$i week"));
            $task->category = 'Smaller tasks';
            $task->createrID = User::findOne(['neptun' => 'inst01'])->id;
            $task->description = '';

            if ($task->save()) {
                $this->stdout("Successfully inserted task ('$task->name')." . PHP_EOL, Console::FG_GREEN);
            } else {
                $this->stdout("Failed to insert task ('$task->name')." . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        // Seed Submissions
        try {
            // First Task
            $this->seedSubmission(5, 'stud01', 1, StudentFile::IS_ACCEPTED_ACCEPTED, 2, StudentFile::AUTO_TESTER_STATUS_NOT_TESTED);
            $this->seedSubmission(6, 'stud02', 1, StudentFile::IS_ACCEPTED_PASSED, null, StudentFile::AUTO_TESTER_STATUS_PASSED);
            $this->seedSubmission(7, 'stud03', 1, StudentFile::IS_ACCEPTED_ACCEPTED, 2, StudentFile::AUTO_TESTER_STATUS_NOT_TESTED);
            $this->seedSubmission(8, 'stud04', 1, StudentFile::IS_ACCEPTED_ACCEPTED, 2, StudentFile::AUTO_TESTER_STATUS_NOT_TESTED);
            $this->seedSubmission(9, 'stud05', 1, StudentFile::IS_ACCEPTED_PASSED, null, StudentFile::AUTO_TESTER_STATUS_PASSED);
            $this->seedSubmission(10, 'stud06', 1, StudentFile::IS_ACCEPTED_LATE_SUBMISSION, 2, StudentFile::AUTO_TESTER_STATUS_NOT_TESTED);
            // Second Task
            $this->seedSubmission(5, 'stud01', 2, StudentFile::IS_ACCEPTED_REJECTED, 2, StudentFile::AUTO_TESTER_STATUS_NOT_TESTED);
            $this->seedSubmission(6, 'stud02', 2, StudentFile::IS_ACCEPTED_REJECTED, 2, StudentFile::AUTO_TESTER_STATUS_NOT_TESTED);
            $this->seedSubmission(7, 'stud03', 2, StudentFile::IS_ACCEPTED_UPLOADED, null, StudentFile::AUTO_TESTER_STATUS_NOT_TESTED);
            $this->seedSubmission(8, 'stud04', 2, StudentFile::IS_ACCEPTED_FAILED, null, StudentFile::AUTO_TESTER_STATUS_TESTS_FAILED);
            $this->seedSubmission(9, 'stud05', 2, StudentFile::IS_ACCEPTED_ACCEPTED, 2, StudentFile::AUTO_TESTER_STATUS_NOT_TESTED);
            $this->seedSubmission(10, 'stud06', 2, StudentFile::IS_ACCEPTED_ACCEPTED, 2, StudentFile::AUTO_TESTER_STATUS_NOT_TESTED);
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Copy submission files into data directory
        FileHelper::copyDirectory('sampledata', Yii::$app->params['data_dir']);

        // Seed Question Set
        $questionSet = new ExamQuestionSet();
        $questionSet->id = 1;
        $questionSet->courseID = 1;
        $questionSet->name = "Quick question set";
        if ($questionSet->save()) {
            $this->stdout("Successfully inserted initial question set '$questionSet->name'." . PHP_EOL, Console::FG_GREEN);
        } else {
            $this->stdout("Failed to insert initial question set '$questionSet->name'." . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        for ($i = 0; $i < 5; ++$i) {
            $question = new ExamQuestion();
            $question->id = $i + 1;
            $question->text = "Question " . ($i + 1);
            $question->questionsetID = $questionSet->id;
            $question->save();
            for ($j = 0; $j < 5; ++$j) {
                $answer = new ExamAnswer();
                $answer->text = "Answer " . ($j + 1);
                $answer->correct = ($j == 1) ? 1 : 0;
                $answer->questionID = $question->id;
                $answer->save();
            }
        }

        // Seed Test and Test Instance
        $test = new ExamTest();
        $test->id = 1;
        $test->name = "Quick questions";
        $test->questionamount = ExamQuestion::find()->count();
        $test->duration = 60;
        $test->shuffled = 1;
        $test->unique = 0;
        $test->questionsetID = 1;
        $test->groupID = 1;
        $test->availablefrom = date('Y-m-d H:i:s');
        $test->availableuntil = date('Y-m-d H:i:s', strtotime('+7 day'));

        $test2 = new ExamTest();
        $test2->id = 2;
        $test2->name = "Exam";
        $test2->questionamount = ExamQuestion::find()->count();
        $test2->duration = 60;
        $test2->shuffled = 1;
        $test2->unique = 0;
        $test2->questionsetID = 1;
        $test2->groupID = 1;
        $test2->availablefrom = date('Y-m-d H:i:s');
        $test2->availableuntil = date('Y-m-d H:i:s', strtotime('+14 day'));

        if (!$test->save() || !$test2->save()) {
            $this->stdout("Failed to insert initial tests." . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        } else {
            $test->finalize();
            $test2->finalize();

            $this->stdout("Successfully inserted initial tests." . PHP_EOL, Console::FG_GREEN);
        }

        return ExitCode::OK;
    }

    /**
     * Truncates the database and data from disk.
     *
     * @return int Error code.
     * @throws \yii\base\ErrorException
     */
    public function actionPrune(): int
    {
        $prune = true;
        if ($this->interactive && !$this->promptBoolean('Are you sure you want to truncate the database and delete appdata folder from disk? (Y|N)', false)) {
            $prune = false;
        }

        if ($prune) {
            // Truncate database
            $this->run('migrate/fresh', ['interactive' => 0]);

            // Delete appdata folder
            $this->deleteFolderContents(Yii::$app->basePath . '/' . Yii::$app->params['data_dir']);
        }

        return ExitCode::OK;
    }

    /**
     * Deletes the content of a folder, but not the folder itself.
     * @throws \yii\base\ErrorException
     */
    private function deleteFolderContents(string $path): void
    {
        if (is_dir($path)) {
            $fileSystemIterator = new \FilesystemIterator($path);
            foreach ($fileSystemIterator as $file) {
                $file->isDir() ? FileHelper::removeDirectory($file) : FileHelper::unlink($file);
            }
        }
    }
}
