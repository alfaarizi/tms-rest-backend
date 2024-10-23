<?php

namespace app\tests\api;

use ApiTester;
use app\models\Submission;
use app\models\Task;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\SubmissionsFixture;
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
            'submission' => [
                'class' => SubmissionsFixture::class,
            ],
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->deleteDir(Yii::getAlias("@appdata"));
        $I->copyDir(codecept_data_dir("appdata_samples"), Yii::getAlias("@appdata"));
        $I->amBearerAuthenticated("TEACH2;VALID");
        Yii::$app->language = 'en-US';
    }

    public function _after(ApiTester $I)
    {
        $I->deleteDir(Yii::getAlias("@appdata"));
    }

    public function setupAutoTesterReevaluateOff(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks/5015/evaluator/setup-auto-tester',
            [
                'autoTest' => true,
                'appType' => 'Console',
                'compileInstructions' => '/compile.sh',
                'runInstructions' => './program.out',
                'showFullErrorMsg' => true,
                'reevaluateAutoTest' => false,
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
            Submission::class,
            [
                'id' => 17,
                'status' => Submission::STATUS_PASSED,
                'autoTesterStatus' => Submission::AUTO_TESTER_STATUS_PASSED,
            ]
        );
        $I->seeRecord(
            Submission::class,
            [
                'id' => 18,
                'status' => Submission::STATUS_FAILED,
                'autoTesterStatus' => Submission::AUTO_TESTER_STATUS_EXECUTION_FAILED,
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
            Submission::class,
            [
                'id' => 17,
                'status' => Submission::STATUS_UPLOADED,
                'autoTesterStatus' => Submission::AUTO_TESTER_STATUS_NOT_TESTED,
            ]
        );
        $I->seeRecord(
            Submission::class,
            [
                  'id' => 18,
                  'status' => Submission::STATUS_UPLOADED,
                  'autoTesterStatus' => Submission::AUTO_TESTER_STATUS_NOT_TESTED,
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

    public function setupCodeCheckerValid(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks/5015/evaluator/setup-code-checker',
            [
                'staticCodeAnalysis' => true,
                'staticCodeAnalyzerTool' => 'codechecker',
                'codeCheckerCompileInstructions' => 'g++ *.cpp',
                'codeCheckerToggles' => '--toggle1',
                'codeCheckerSkipFile' => '- */skipped.cpp',
                'reevaluateStaticCodeAnalysis' => false,
            ]
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 5015,
                'staticCodeAnalysis' => 1,
                'staticCodeAnalyzerTool' => 'codechecker',
                'codeCheckerCompileInstructions' => 'g++ *.cpp',
                'codeCheckerToggles' => '--toggle1',
                'codeCheckerSkipFile' => '- */skipped.cpp',
            ]
        );
        $I->seeRecord(
            Task::class,
            [
                'id' => 5015,
                'staticCodeAnalysis' => 1,
                'staticCodeAnalyzerTool' => 'codechecker',
                'codeCheckerCompileInstructions' => 'g++ *.cpp',
                'codeCheckerToggles' => '--toggle1',
                'codeCheckerSkipFile' => '- */skipped.cpp',
            ]
        );

        // Student file has not changed
        $I->seeRecord(
            Submission::class,
            [
                'id' => 17,
                'codeCheckerResultID' => 3,
            ]
        );
    }

    public function setupCodeCheckerValidReevaluate(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks/5015/evaluator/setup-code-checker',
            [
                'staticCodeAnalysis' => true,
                'staticCodeAnalyzerTool' => 'codechecker',
                'codeCheckerCompileInstructions' => 'g++ *.cpp',
                'codeCheckerToggles' => '--toggle1',
                'codeCheckerSkipFile' => '- */skipped.cpp',
                'reevaluateStaticCodeAnalysis' => true,
            ]
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 5015,
                'staticCodeAnalysis' => 1,
                'staticCodeAnalyzerTool' => 'codechecker',
                'codeCheckerCompileInstructions' => 'g++ *.cpp',
                'codeCheckerToggles' => '--toggle1',
                'codeCheckerSkipFile' => '- */skipped.cpp',
            ]
        );
        $I->seeRecord(
            Task::class,
            [
                'id' => 5015,
                'staticCodeAnalysis' => 1,
                'staticCodeAnalyzerTool' => 'codechecker',
                'codeCheckerCompileInstructions' => 'g++ *.cpp',
                'codeCheckerToggles' => '--toggle1',
                'codeCheckerSkipFile' => '- */skipped.cpp',
            ]
        );

        // Student file has changed
        $I->seeRecord(
            Submission::class,
            [
                'id' => 17,
                'codeCheckerResultID' => null,
            ]
        );
    }

    public function setupCodeCheckerMissingField(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks/5015/evaluator/setup-code-checker',
            [
                'staticCodeAnalysis' => true,
                'staticCodeAnalyzerTool' => 'codechecker',
                'codeCheckerToggles' => '--toggle1',
                'codeCheckerSkipFile' => '- */skipped.cpp',
                'reevaluateStaticCodeAnalysis' => false,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
    }

    public function setupCodeCheckerOtherTool(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks/5015/evaluator/setup-code-checker',
            [
                'staticCodeAnalysis' => true,
                'staticCodeAnalyzerTool' => 'roslynator',
                'staticCodeAnalyzerInstructions' => 'roslynator analyze',
                'codeCheckerSkipFile' => '- */skipped.cs',
                'reevaluateStaticCodeAnalysis' => false,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TASK_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'id' => 5015,
                'staticCodeAnalysis' => 1,
                'staticCodeAnalyzerTool' => 'roslynator',
                'staticCodeAnalyzerInstructions' => 'roslynator analyze',
                'codeCheckerSkipFile' => '- */skipped.cs',
            ]
        );
        $I->seeRecord(
            Task::class,
            [
                'id' => 5015,
                'staticCodeAnalysis' => 1,
                'staticCodeAnalyzerTool' => 'roslynator',
                'staticCodeAnalyzerInstructions' => 'roslynator analyze',
                'codeCheckerSkipFile' => '- */skipped.cs',
            ]
        );

        // Student file not changed
        $I->seeRecord(
            Submission::class,
            [
                'id' => 17,
                'codeCheckerResultID' => 3,
            ]
        );
    }

    public function setupCodeCheckerUnknownTool(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks/5015/evaluator/setup-code-checker',
            [
                'staticCodeAnalysis' => true,
                'staticCodeAnalyzerTool' => 'unknown',
                'staticCodeAnalyzerInstructions' => 'roslynator analyze',
                'codeCheckerSkipFile' => '- */skipped.cs',
                'reevaluateStaticCodeAnalysis' => false,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
    }


    public function setupCodeCheckerTaskNotFound(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks/0/evaluator/setup-code-checker',
            [
                'staticCodeAnalysis' => 1,
                'staticCodeAnalyzerTool' => 'codechecker',
                'codeCheckerCompileInstructions' => 'g++ *.cpp',
                'codeCheckerToggles' => '--toggle1',
                'codeCheckerSkipFile' => '- */skipped.cpp',
                'reevaluateStaticCodeAnalysis' => 1,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function setupCodeCheckerPreviousSemester(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks/5005/evaluator/setup-code-checker',
            [
                'staticCodeAnalysis' => 1,
                'staticCodeAnalyzerTool' => 'codechecker',
                'codeCheckerCompileInstructions' => 'g++ *.cpp',
                'codeCheckerToggles' => '--toggle1',
                'codeCheckerSkipFile' => '- */skipped.cpp',
                'reevaluateStaticCodeAnalysis' => 1,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function setupCodeCheckerWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            '/instructor/tasks/5004/evaluator/setup-code-checker',
            [
                'staticCodeAnalysis' => true,
                'staticCodeAnalyzerTool' => 'codechecker',
                'codeCheckerCompileInstructions' => 'g++ *.cpp',
                'codeCheckerToggles' => '--toggle1',
                'codeCheckerSkipFile' => '- */skipped.cpp',
                'reevaluateStaticCodeAnalysis' => false,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }
}
