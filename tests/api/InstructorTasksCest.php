<?php

namespace tests\api;

use ApiTester;
use app\models\InstructorFile;
use app\models\StudentFile;
use app\models\Task;
use app\models\TestCase;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\GroupFixture;
use app\tests\unit\fixtures\InstructorFilesFixture;
use app\tests\unit\fixtures\StudentFilesFixture;
use app\tests\unit\fixtures\SubscriptionFixture;
use app\tests\unit\fixtures\TaskFixture;
use app\tests\unit\fixtures\TestCaseFixture;
use app\tests\unit\fixtures\UserFixture;
use Codeception\Util\HttpCode;
use League\Uri\Http;
use Yii;

class InstructorTasksCest
{
    public const TASK_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'category' => 'string',
        'translatedCategory' => 'string',
        'description' => 'string',
        'softDeadline' => 'string|null',
        'hardDeadline' => 'string',
        'available' => 'string|null',
        'autoTest' => 'integer|null|string',
        'showFullErrorMsg' => 'integer|null|string',
        'isVersionControlled' => 'integer|null|string',
        'groupID' => 'integer|string',
        'semesterID' => 'integer|string',
        'creatorName' => 'string',
        'codeCompassCompileInstructions' => 'string|null',
        'codeCompassPackagesInstallInstructions' => 'string|null'
    ];

    public const USER_SCHEMA = [
        'id' => 'integer',
        'neptun' => 'string',
        'name' => 'string'
    ];

    public function _fixtures()
    {
        return [
            'accesstokens' => [
                'class' => AccessTokenFixture::class,
            ],
            'tasks' => [
                'class' => TaskFixture::class,
            ],
            'groups' => [
                'class' => GroupFixture::class
            ],
            'users' => [
                'class' => UserFixture::class
            ],
            'subscriptions' => [
                'class' => SubscriptionFixture::class
            ],
            'testcases' => [
                'class' => TestCaseFixture::class
            ],
            'instructorfiles' => [
                'class' => InstructorFilesFixture::class
            ],
            'studentfiles' => [
                'class' => StudentFilesFixture::class
            ]
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->deleteDir(Yii::$app->params['data_dir']);
        $I->copyDir(codecept_data_dir("appdata_samples"), Yii::$app->params['data_dir']);
        $I->amBearerAuthenticated("TEACH2;VALID");
        Yii::$app->language = 'en-US';
    }

    public function _after(ApiTester $I)
    {
        $I->deleteDir(Yii::$app->params['data_dir']);
    }

    public function indexGroupNotFound(ApiTester $I)
    {
        $I->sendGet('/instructor/tasks?groupID=0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function indexWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/instructor/tasks?groupID=2001');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function index(ApiTester $I)
    {
        $I->sendGet('/instructor/tasks?groupID=2000');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA, '$.[*].[*]');

        $I->seeResponseContainsJson([['id' => 5000]]);
        $I->seeResponseContainsJson([['id' => 5001]]);
        $I->seeResponseContainsJson([['id' => 5002]]);
        $I->seeResponseContainsJson([['id' => 5003]]);
        $I->seeResponseContainsJson([['id' => 5008]]);
        $I->seeResponseContainsJson([['id' => 5010]]);
        $I->seeResponseContainsJson([['id' => 5011]]);
        $I->seeResponseContainsJson([['id' => 5012]]);
        $I->seeResponseContainsJson([['id' => 5013]]);
        $I->seeResponseContainsJson([['id' => 5014]]);

        $I->cantSeeResponseContainsJson([['id' => 5004]]);
        $I->cantSeeResponseContainsJson([['id' => 5005]]);
        $I->cantSeeResponseContainsJson([['id' => 5006]]);
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet('/instructor/tasks/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function viewWithoutPermission(ApiTester $I)
    {
        $I->sendGet('/instructor/tasks/5004');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function view(ApiTester $I)
    {
        $I->sendGet('/instructor/tasks/5000');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 5000,
                'name' => 'Task 1',
                'category' => 'Larger tasks',
                'translatedCategory' => 'Larger tasks',
                'description' => 'Description',
                'hardDeadline' => '2021-03-08T10:00:00+01:00',
                'softDeadline' => null,
                'available' => null,
                'autoTest' => 0,
                'showFullErrorMsg' => 0,
                'isVersionControlled' => 0,
                'groupID' => 2000,
                'semesterID' => 3001,
                'creatorName' => 'Teacher Two',
                'codeCompassCompileInstructions' => 'sudo magic compile command',
                'codeCompassPackagesInstallInstructions' => 'apt-get install qt5 wireshark -y',
            ]
        );
    }

    public function createWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks',
            [
                'name' => 'Created',
                'groupID' => 2007,
                'softDeadLine' => date('Y-m-d H:i:s', strtotime('+1 day')),
                'hardDeadline' => date('Y-m-d H:i:s', strtotime('+2 day')),
                'category' => 'Smaller tasks',
                'description' => 'Description',
                'isVersionControlled' => 0
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->cantSeeRecord(Task::class, ['name' => 'Created']);
    }

    public function createInvalid(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks',
            [
                'name' => 'Created',
                'groupID' => 2000,
                'category' => 'Smaller tasks',
                'description' => 'Description',
                'isVersionControlled' => 0
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
        $I->cantSeeRecord(Task::class, ['name' => 'Created']);
    }

    public function createFromPreviousSemester(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks',
            [
                'name' => 'Created',
                'softDeadLine' => date('Y-m-d H:i:s', strtotime('+1 day')),
                'hardDeadline' => date('Y-m-d H:i:s', strtotime('+2 day')),
                'groupID' => 2010,
                'category' => 'Smaller tasks',
                'description' => 'Description',
                'isVersionControlled' => 0
            ]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => "You can't modify a group from a previous semester!"
            ]
        );
        $I->cantSeeRecord(Task::class, ['name' => 'Created']);
    }

    public function createValid(ApiTester $I)
    {
        $data = [
            'name' => 'Created',
            'softDeadLine' => date(\DateTime::ATOM, strtotime('+1 day')),
            'hardDeadline' => date(\DateTime::ATOM, strtotime('+2 day')),
            'groupID' => 2000,
            'category' => 'Smaller tasks',
            'description' => 'Description',
            'isVersionControlled' => 0,
            'autoTest' => 0
        ];

        $I->sendPost(
            '/instructor/tasks',
            $data
        );
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA);

        $I->seeResponseContainsJson(
            [
                'name' => $data['name'],
                'category' => $data['category'],
                'translatedCategory' => 'Smaller tasks',
                'description' => $data['description'],
                'hardDeadline' => $data['hardDeadline'],
                'softDeadline' => null,
                'available' => null,
                'autoTest' => null,
                'isVersionControlled' => $data['isVersionControlled'],
                'groupID' => $data['groupID'],
                'semesterID' => 3001,
                'creatorName' => 'Teacher Two',
            ]
        );

        $I->seeRecord(Task::class, ['name' => 'Created']);
        $I->seeEmailIsSent(3);
    }

    public function updateNotFound(ApiTester $I)
    {
        $I->sendPatch('/instructor/tasks/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeEmailIsSent(0);
    }

    public function updateWithoutPermission(ApiTester $I)
    {
        $I->sendPatch('/instructor/tasks/5004');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeEmailIsSent(0);
    }

    public function updatePreviousSemester(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/tasks/5005',
            [
                'name' => 'Updated',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => "You can't modify a task from a previous semester!"
            ]
        );
        $I->cantSeeRecord(Task::class, ['name' => 'Updated']);
        $I->seeEmailIsSent(0);
    }

    public function updateTaskFromCanvasCourse(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/tasks/5006',
            [
                'name' => 'Updated'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => 'This operation cannot be performed on a canvas synchronized course!'
            ]
        );
        $I->seeRecord(Task::class, ['id' => 5006, 'name' => 'Task 7']);
    }

    public function updateInvalid(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/tasks/5000',
            [
                'hardDeadline' => '',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
        $I->cantSeeRecord(Task::class, ['name' => 'Updated']);
        $I->seeEmailIsSent(0);
    }

    public function updateValidDoesntChangeDeadline(ApiTester $I)
    {
        $I->sendPatch(
            '/instructor/tasks/5000',
            [
                'name' => 'Updated',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA);

        $I->seeResponseContainsJson(
            [
                'id' => 5000,
                'name' => 'Updated',
                'category' => 'Larger tasks',
                'translatedCategory' => 'Larger tasks',
                'description' => 'Description',
                'hardDeadline' => '2021-03-08T10:00:00+01:00',
                'softDeadline' => null,
                'available' => null,
                'autoTest' => 0,
                'isVersionControlled' => 0,
                'groupID' => 2000,
                'semesterID' => 3001,
                'creatorName' => 'Teacher Two',
            ]
        );

        $I->seeRecord(Task::class, ['name' => 'Updated']);
        $I->seeEmailIsSent(0);
    }

    public function updateValidChangeHardDeadline(ApiTester $I)
    {
        $date = date('Y-m-d H:i:s', strtotime('+7 day'));
        $I->sendPatch(
            '/instructor/tasks/5000',
            [
                'name' => 'Updated',
                'hardDeadline' => $date
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA);
        $I->seeRecord(Task::class, ['name' => 'Updated', 'hardDeadline' => $date]);
        $I->seeEmailIsSent(3);
    }

    public function updateValidChangeSoftDeadline(ApiTester $I)
    {
        $date = date('Y-m-d H:i:s', strtotime('+7 day'));
        $I->sendPatch(
            '/instructor/tasks/5000',
            [
                'name' => 'Updated',
                'softDeadline' => $date
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA);
        $I->seeRecord(Task::class, ['name' => 'Updated', 'softDeadline' => $date]);
        $I->seeEmailIsSent(3);
    }

    public function updateValidChangeAvailable(ApiTester $I)
    {
        $date = date('Y-m-d H:i:s', strtotime('+7 day'));
        $I->sendPatch(
            '/instructor/tasks/5000',
            [
                'name' => 'Updated',
                'available' => $date
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA);
        $I->seeRecord(Task::class, ['name' => 'Updated', 'available' => $date]);
        $I->seeEmailIsSent(3);
    }

    public function deleteNotFound(ApiTester $I)
    {
        $I->sendDelete('/instructor/tasks/0');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function deleteWithoutPermission(ApiTester $I)
    {
        $I->sendDelete('/instructor/tasks/5004');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeRecord(Task::class, ['id' => 5004]);
    }

    public function deleteFromPreviousSemester(ApiTester $I)
    {
        $I->sendDelete('/instructor/tasks/5005');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => "You can't modify a task from a previous semester!"
            ]
        );
        $I->seeRecord(Task::class, ['id' => 5005]);
    }

    public function deleteFromCanvasCourse(ApiTester $I)
    {
        $I->sendDelete('/instructor/tasks/5006');
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => 'This operation cannot be performed on a canvas synchronized course!'
            ]
        );
        $I->seeRecord(Task::class, ['id' => 5006]);
    }


    public function delete(ApiTester $I)
    {
        $I->sendDelete('/instructor/tasks/5000');
        $I->seeResponseCodeIs(HttpCode::NO_CONTENT);
        $I->cantSeeRecord(Task::class, ['id' => 5000]);

        // Delete test cases
        $I->cantSeeRecord(TestCase::class, ['id' => 1]);
        $I->cantSeeRecord(TestCase::class, ['id' => 2]);

        // Delete files
        $I->cantSeeRecord(InstructorFile::class, ['id' => 1]);
        $I->cantSeeRecord(InstructorFile::class, ['id' => 2]);
        $I->cantSeeRecord(InstructorFile::class, ['id' => 3]);
    }


    public function toggleAutoTesterNotFound(ApiTester $I)
    {
        $I->sendPatch("/instructor/tasks/0/toggle-auto-tester");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function toggleAutoTesterWithoutPermission(ApiTester $I)
    {
        $I->sendPatch("/instructor/tasks/5004/toggle-auto-tester");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function toggleAutoTestPreviousSemester(ApiTester $I)
    {
        $I->sendPatch("/instructor/tasks/5005/toggle-auto-tester");
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function toggleAutoTester(ApiTester $I)
    {
        // Turn on
        $I->sendPatch("/instructor/tasks/toggle-auto-tester?id=5000");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(
            [
                'id' => 5000,
                'autoTest' => 1
            ]
        );
        $I->seeRecord(
            Task::class,
            [
                'id' => 5000,
                'autoTest' => 1
            ]
        );

        // Turn off
        $I->sendPatch("/instructor/tasks/toggle-auto-tester?id=5000");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(
            [
                'id' => 5000,
                'autoTest' => 0
            ]
        );
        $I->seeRecord(
            Task::class,
            [
                'id' => 5000,
                'autoTest' => 0
            ]
        );
    }

    public function listUsers(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/tasks/list-users",
            [
                "ids" => [5000, 5004]
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::USER_SCHEMA, '$.[*]');
        $I->seeResponseContainsJson([['id' => 1001]]);
        $I->seeResponseContainsJson([['id' => 1002]]);
        $I->seeResponseContainsJson([['id' => 1003]]);

        $I->cantSeeResponseContainsJson([['id' => 1004]]);
        $I->cantSeeResponseContainsJson([['id' => 1005]]);
    }

    public function listUsersTaskNotFound(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/tasks/list-users",
            [
                "ids" => [0, 1, 5]
            ]
        );
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function listForCourse(ApiTester $I)
    {
        $I->sendGet(
            "/instructor/tasks/list-for-course",
            [
                "courseID" => 4000,
                "myTasks" => true,
                "semesterFromID" => 3000,
                "semesterToID" => 3001
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA, "$.[*]");
        $I->seeResponseContainsJson(['id' => 5000]);
        $I->seeResponseContainsJson(['id' => 5001]);
        $I->seeResponseContainsJson(['id' => 5002]);
        $I->seeResponseContainsJson(['id' => 5003]);
        $I->seeResponseContainsJson(['id' => 5006]);
        $I->seeResponseContainsJson([['id' => 5010]]);
        $I->seeResponseContainsJson([['id' => 5011]]);
        $I->seeResponseContainsJson([['id' => 5012]]);
        $I->seeResponseContainsJson([['id' => 5013]]);
        $I->seeResponseContainsJson([['id' => 5014]]);

        $I->cantSeeResponseContainsJson(['id' => 5004]);
        $I->cantSeeResponseContainsJson(['id' => 5005]);
        $I->cantSeeResponseContainsJson(['id' => 5007]);
    }

    /**
     * Check if the statuses of the student files has been updated after the password has been removed
     * @param ApiTester $I
     * @return void
     */
    public function removePasswordFromTask(ApiTester $I)
    {
        $I->seeRecord(
            StudentFile::class,
            [
                'id' => 12,
                'isAccepted' => StudentFile::IS_ACCEPTED_UPLOADED,
                'verified' => false
            ]
        );

        $I->seeRecord(
            StudentFile::class,
            [
                'id' => 14,
                'isAccepted' => \app\models\StudentFile::IS_ACCEPTED_ACCEPTED,
                'verified' => true
            ]
        );

        $I->sendPatch(
            '/instructor/tasks/5011',
            ['password' => '']
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA);
        $I->seeResponseContainsJson(['password' => '']);

        $I->seeRecord(
            StudentFile::class,
            [
                'id' => 12,
                'isAccepted' => StudentFile::IS_ACCEPTED_UPLOADED,
                'verified' => true
            ]
        );

        $I->seeRecord(
            StudentFile::class,
            [
                'id' => 14,
                'isAccepted' => \app\models\StudentFile::IS_ACCEPTED_ACCEPTED,
                'verified' => true
            ]
        );
    }

    public function setupCodeCompassParserNotFound(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/tasks/0/setup-code-compass-parser",
            [
                'codeCompassCompileInstructions' => 'sudo magic',
                'codeCompassPackagesInstallInstructions' => 'apt-get install wpf qt etc -y'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function setupCodeCompassParserWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/tasks/5004/setup-code-compass-parser",
            [
                'codeCompassCompileInstructions' => 'sudo magic',
                'codeCompassPackagesInstallInstructions' => 'apt-get install wpf qt etc -y'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function setupCodeCompassParserWithoutPackageChange(ApiTester $I)
    {
        $I->sendPost(
            "/instructor/tasks/5000/setup-code-compass-parser",
            [
                'codeCompassCompileInstructions' => 'sudo compile program',
                'codeCompassPackagesInstallInstructions' => 'apt-get install qt5 wireshark -y'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA);

        $I->seeResponseContainsJson(
            [
                'id' => 5000,
                'name' => 'Task 1',
                'category' => 'Larger tasks',
                'translatedCategory' => 'Larger tasks',
                'description' => 'Description',
                'hardDeadline' => '2021-03-08T10:00:00+01:00',
                'softDeadline' => null,
                'available' => null,
                'autoTest' => 0,
                'showFullErrorMsg' => 0,
                'isVersionControlled' => 0,
                'groupID' => 2000,
                'semesterID' => 3001,
                'creatorName' => 'Teacher Two',
                'codeCompassCompileInstructions' => 'sudo compile program',
                'codeCompassPackagesInstallInstructions' => 'apt-get install qt5 wireshark -y',
            ]
        );
    }
}
