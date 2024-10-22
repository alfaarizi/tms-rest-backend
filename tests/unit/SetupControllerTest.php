<?php

namespace app\tests\unit;

use app\commands\SetupController;
use app\models\Course;
use app\models\CourseCode;
use app\models\ExamAnswer;
use app\models\ExamQuestion;
use app\models\ExamQuestionSet;
use app\models\ExamTest;
use app\models\ExamTestInstance;
use app\models\Group;
use app\models\InstructorGroup;
use app\models\Semester;
use app\models\StudentFile;
use app\models\Subscription;
use app\models\Task;
use app\models\User;
use Yii;
use yii\base\Module;
use yii\helpers\FileHelper;

class SetupControllerTest extends \Codeception\Test\Unit
{
    protected \UnitTester $tester;

    /**
     * Tests setup/init method
     * @return void
     */
    public function testInit()
    {
        $controller = new SetupController('null', new Module('test'));
        $controller->interactive = false;
        $controller->actionInit();

        $this->assertEquals(1, Semester::find()->count());
        $this->assertEquals(1, User::find()->count());

        $this->tester->seeRecord(Semester::class, [
            'actual' => true
        ]);
        $this->tester->seeRecord(User::class, [
            'userCode' => 'admr01',
            'name' => 'administrator01'
        ]);
    }

    /**
     * Tests setup/sample method
     * @return void
     */
    public function testSample()
    {
        $controller = new SetupController('null', new Module('test'));
        $controller->interactive = false;
        $controller->actionSample();

        $this->assertEquals(1, Semester::find()->count());
        $this->assertEquals(10, User::find()->count());
        $this->assertEquals(1, Course::find()->count());
        $this->assertEquals(1, Group::find()->count());
        $this->assertEquals(3, InstructorGroup::find()->count());
        $this->assertEquals(6, Subscription::find()->count());
        $this->assertEquals(2, Task::find()->count());
        $this->assertEquals(12, StudentFile::find()->count());
        $this->assertEquals(1, ExamQuestionSet::find()->count());
        $this->assertEquals(5, ExamQuestion::find()->count());
        $this->assertEquals(25, ExamAnswer::find()->count());
        $this->assertEquals(2, ExamTest::find()->count());
        $this->assertEquals(12, ExamTestInstance::find()->count());

        $this->tester->seeRecord(Semester::class, [
            'actual' => true
        ]);
        $this->tester->seeRecord(User::class, [
            'userCode' => 'admr01',
            'name' => 'administrator01'
        ]);
        $this->tester->seeRecord(User::class, [
            'id' => 1,
            'userCode' => 'admr01',
            'name' => 'administrator01'
        ]);
        $this->tester->seeRecord(User::class, [
            'id' => 4,
            'userCode' => 'inst03',
            'name' => 'instructor03',
            'email' => 'instructor03@example.com'
        ]);
        $this->tester->seeRecord(User::class, [
            'id' => 10,
            'userCode' => 'stud06',
            'name' => 'student06',
            'email' => 'student06@example.com'
        ]);
        $this->tester->seeRecord(Course::class, [
            'id' => 1,
            'name' => 'Development of web based applications',
        ]);
        $this->tester->seeRecord(CourseCode::class, [
            'id' => 1,
            'courseId' => 1,
            'code' => ['IP-08bWAFEG']
        ]);
        $this->tester->seeRecord(Group::class, [
            'id' => 1,
            'courseID' => 1,
            'semesterID' => 1,
            'number' => 1,
            'timezone' => \Yii::$app->timeZone
        ]);
        $this->tester->seeRecord(InstructorGroup::class, [
            'userID' => 4,
            'groupID' => 1
        ]);
        $this->tester->seeRecord(Subscription::class, [
            'userID' => 10,
            'groupID' => 1,
            'semesterID' => 1,
            'isAccepted' => 1,
            'notes' => 'Notes'
        ]);
        $this->tester->seeRecord(Task::class, [
            'id' => 2,
            'name' => 'Task 2',
            'semesterID' => 1,
            'groupID' => 1,
            'category' => 'Smaller tasks',
            'createrID' => 2,
            'description' => ''
        ]);
        $this->tester->seeRecord(StudentFile::class, [
            'name' => 'stud06.zip',
            'taskID' => 2,
            'uploaderID' => 10,
            'isAccepted' => 'Accepted',
            'autoTesterStatus' => 'Not Tested',
            'verified' => 1,
            'uploadCount' => 1,
            'grade' => null,
            'notes' => ''
        ]);
        $this->tester->seeRecord(ExamQuestionSet::class, [
            'courseID' => 1,
            'name' => 'Quick question set'
        ]);
        $this->tester->seeRecord(ExamQuestion::class, [
            'id' => 5,
            'text' => 'Question 5',
            'questionsetID' => 1
        ]);
        $this->tester->seeRecord(ExamAnswer::class, [
            'text' => 'Answer 5',
            'correct' => 0,
            'questionID' => 5
        ]);
        $this->tester->seeRecord(ExamTest::class, [
            'id' => 2,
            'name' => 'Exam',
            'questionamount' => 5,
            'duration' => 60,
            'shuffled' => 1,
            'unique' => 0,
            'questionsetID' => 1,
            'groupID' => 1
        ]);
        $this->tester->seeRecord(ExamTestInstance::class, [
            'score' => 0,
            'submitted' => false,
            'userID' => 10,
            'testID' => 2
        ]);

        FileHelper::removeDirectory(Yii::getAlias("@appdata"));
    }
}
