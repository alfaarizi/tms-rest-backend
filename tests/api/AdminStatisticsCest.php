<?php

namespace app\tests\api;

use ApiTester;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\GroupFixture;
use app\tests\unit\fixtures\StudentFilesFixture;
use app\tests\unit\fixtures\TaskFixture;
use Codeception\Util\HttpCode;
use Yii;

class AdminStatisticsCest
{
    public const STATS_SCHEMA = [
        'groupsCount' => 'integer',
        'tasksCount' => 'integer',
        'submissionsCount' => 'integer',
        'testedSubmissionCount' => 'integer',
        'submissionsUnderTestingCount' => 'integer',
        'submissionsToBeTested' => 'integer',
        'diskFree' => 'float|integer|null'
    ];

    public const STATS_SEMESTER_SCHEMA = [
        'groupsCount' => 'integer',
        'tasksCount' => 'integer',
        'submissionsCount' => 'integer',
        'testedSubmissionCount' => 'integer'
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
            'studentfiles' => [
                'class' => StudentFilesFixture::class
            ],
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->amBearerAuthenticated("BATMAN;12345");
    }

    public function _after(ApiTester $I)
    {
        $I->deleteDir(Yii::getAlias("@appdata"));
    }

    public function index(ApiTester $I)
    {
        $I->deleteDir(Yii::getAlias("@appdata"));
        $I->copyDir(codecept_data_dir("appdata_samples"), Yii::getAlias("@appdata"));
        $I->sendGet('/admin/statistics', ['semesterID' => 3001]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STATS_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'groupsCount' => 11,
                'tasksCount' => 19,
                'submissionsCount' => 20,
                'testedSubmissionCount' => 7,
                'submissionsUnderTestingCount' => 1,
                'submissionsToBeTested' => 1,
            ]
        );
        $I->dontSeeResponseContainsJson(['diskFree' => null]);
    }

    public function indexNoDirectory(ApiTester $I)
    {
        $I->deleteDir(Yii::getAlias("@appdata"));
        $I->sendGet('/admin/statistics', ['semesterID' => 3001]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STATS_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'groupsCount' => 11,
                'tasksCount' => 19,
                'submissionsCount' => 20,
                'testedSubmissionCount' => 7,
                'submissionsUnderTestingCount' => 1,
                'submissionsToBeTested' => 1,
                'diskFree' => null,
            ]
        );
    }

    public function viewSemester(ApiTester $I)
    {
        $I->sendGet('/admin/statistics/view', ['semesterID' => 3001]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STATS_SEMESTER_SCHEMA);
        $I->seeResponseContainsJson(
            [
                'groupsCount' => 8,
                'tasksCount' => 18,
                'submissionsCount' => 19,
                'testedSubmissionCount' => 7,
            ]
        );
    }

    public function viewSemesterNotFound(ApiTester $I)
    {
        $I->sendGet('/admin/statistics/view', ['semesterID' => -1]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }
}
