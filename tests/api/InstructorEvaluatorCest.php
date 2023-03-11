<?php

namespace tests\api;

use ApiTester;
use app\models\StudentFile;
use app\models\Task;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\StudentFilesFixture;
use app\tests\unit\fixtures\TaskFixture;
use Codeception\Util\HttpCode;
use Yii;

class InstructorEvaluatorCest
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
        'appType' => 'string|null',
        'port' => 'integer|string|null',
        'codeCompassCompileInstructions' => 'string|null',
        'codeCompassPackagesInstallInstructions' => 'string|null'
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
            'studentfiles' => [
                'class' => StudentFilesFixture::class,
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

    public function setupAutoTesterReevaluateOff(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks/5015/evaluator/setup-auto-tester',
            [
                'autoTest' => true,
                'compileInstructions' => '/compile.sh',
                'runInstructions' => './program.out',
                'showFullErrorMsg' => true,
                'appType' => 'Console'
            ]
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'compileInstructions' => '/compile.sh',
                'runInstructions' => './program.out',
                'showFullErrorMsg' => 1,
                'appType' => 'Console'
            ]
        );
        $I->seeRecord(
            Task::class,
            [
                'id' => 5015,
                'compileInstructions' => '/compile.sh',
                'runInstructions' => './program.out',
                'showFullErrorMsg' => 1,
                'appType' => 'Console'
            ]
        );
        $I->seeRecord(
            StudentFile::class,
            [
                'id' => 17,
                'isAccepted' => StudentFile::IS_ACCEPTED_PASSED,
                'autoTesterStatus' => StudentFile::AUTO_TESTER_STATUS_PASSED,
            ]
        );
        $I->seeRecord(
            StudentFile::class,
            [
                'id' => 18,
                'isAccepted' => StudentFile::IS_ACCEPTED_FAILED,
                'autoTesterStatus' => StudentFile::AUTO_TESTER_STATUS_EXECUTION_FAILED,
            ]
        );
    }

    public function setupAutoTesterReevaluateOn(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks/5015/evaluator/setup-auto-tester',
            [
                'autoTest' => true,
                'compileInstructions' => '/compile.sh',
                'runInstructions' => './program.out',
                'showFullErrorMsg' => true,
                'appType' => 'Console',
                'reevaluateAutoTest' => true
            ]
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'compileInstructions' => '/compile.sh',
                'runInstructions' => './program.out',
                'showFullErrorMsg' => 1,
                'appType' => 'Console'
            ]
        );
        $I->seeRecord(
            Task::class,
            [
                'id' => 5015,
                'compileInstructions' => '/compile.sh',
                'runInstructions' => './program.out',
                'showFullErrorMsg' => 1,
                'appType' => 'Console'
            ]
        );

        $I->seeRecord(
            StudentFile::class,
            [
                'id' => 17,
                'isAccepted' => StudentFile::IS_ACCEPTED_UPLOADED,
                'autoTesterStatus' => StudentFile::AUTO_TESTER_STATUS_NOT_TESTED,
            ]
        );
        $I->seeRecord(
            StudentFile::class,
            [
                  'id' => 18,
                  'isAccepted' => StudentFile::IS_ACCEPTED_UPLOADED,
                  'autoTesterStatus' => StudentFile::AUTO_TESTER_STATUS_NOT_TESTED,
              ]
        );
    }

    public function setupAutoTesterNotFound(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks/0/evaluator/setup-auto-tester',
            [
                'autoTest' => true,
                'compileInstructions' => '/compile.sh',
                'runInstructions' => './program.out',
                'showFullErrorMsg' => true,
                'appType' => 'Console'
            ]
        );

        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function setupAutoTesterWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks/5004/evaluator/setup-auto-tester',
            [
                'autoTest' => true,
                'compileInstructions' => '/compile.sh',
                'runInstructions' => './program.out',
                'showFullErrorMsg' => true,
                'appType' => 'Console'
            ]
        );

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function setupAutoTesterPreviousSemester(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks/5005/evaluator/setup-auto-tester',
            [
                'autoTest' => true,
                'compileInstructions' => '/compile.sh',
                'runInstructions' => './program.out',
                'showFullErrorMsg' => true,
                'appType' => 'Console'
            ]
        );

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function setupAutoTesterInvalid(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks/5015/evaluator/setup-auto-tester',
            [
                'showFullErrorMsg' => true
            ]
        );

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
    }
}
