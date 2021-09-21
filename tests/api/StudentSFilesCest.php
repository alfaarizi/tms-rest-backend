<?php

namespace tests\api;

use ApiTester;
use Yii;
use app\models\StudentFile;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\StudentFilesFixture;
use app\tests\unit\fixtures\SubscriptionFixture;
use app\tests\unit\fixtures\TaskFixture;
use app\tests\unit\fixtures\UserFixture;
use Codeception\Util\HttpCode;

class StudentSFilesCest
{
    public const STUDENT_FILES_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'uploadTime' => 'string',
        'isAccepted' => 'string|null',
        'translatedIsAccepted' => 'string',
        'grade' => 'integer|null',
        'notes' => 'string|null',
        'isVersionControlled' => 'integer',
        'graderName' => 'string|null',
        'errorMsg' => 'string|null'
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
                'class' => StudentFilesFixture::class
            ],
            'users' => [
                'class' => UserFixture::class
            ],
            'subscriptions' => [
                'class' => SubscriptionFixture::class
            ],
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->deleteDir(Yii::$app->params['data_dir']);
        $I->copyDir(codecept_data_dir("appdata_samples"), Yii::$app->params['data_dir']);
        $I->amBearerAuthenticated("STUD01;VALID");
        Yii::$app->language = 'en-US';
    }

    public function _after(ApiTester $I)
    {
        $I->deleteDir(Yii::$app->params['data_dir']);
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet("/student/student-files/0");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function viewWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/student/student-files/2");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function viewStudentRemovedFromGroup(ApiTester $I)
    {
        $I->sendGet("/student/student-files/5");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function view(ApiTester $I)
    {
        $I->sendGet("/student/student-files/1");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA);

        $I->seeResponseContainsJson(
            [
                'id' => 1,
                'name' => 'stud01.zip',
                'isAccepted' => 'Late Submission',
                'translatedIsAccepted' => 'Late Submission',
                'isVersionControlled' => 0,
                'grade' => '4',
                'notes' => '',
                'graderName' => 'Teacher Two',
                'errorMsg' => ''
            ]
        );
    }

    public function downloadNotFound(ApiTester $I)
    {
        $I->sendGet("/student/student-files/0/download");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function downloadWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/student/student-files/2/download");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function downloadStudentRemovedFromGroup(ApiTester $I)
    {
        $I->sendGet("/student/student-files/5/download");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function download(ApiTester $I)
    {
        $I->sendGet("/student/student-files/1/download");
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->openFile(Yii::$app->params['data_dir'] . "/uploadedfiles/2/stud01/stud01.zip");
        $I->seeFileContentsEqual($I->grabResponse());
    }

    public function uploadInvalid(ApiTester $I)
    {
        $I->sendPost(
            "/student/student-files/upload",
            ['taskID' => 0],
            ['file' => codecept_data_dir("upload_samples/file1.txt")]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
    }

    public function uploadNotAvailable(ApiTester $I)
    {
        $I->sendPost(
            "/student/student-files/upload",
            ['taskID' => 4],
            ['file' => codecept_data_dir("upload_samples/stud01_upload_test.zip")]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function uploadWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            "/student/student-files/upload",
            ['taskID' => 8],
            ['file' => codecept_data_dir("upload_samples/stud01_upload_test.zip")]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function uploadExpired(ApiTester $I)
    {
        $I->sendPost(
            "/student/student-files/upload",
            ['taskID' => 1],
            ['file' => codecept_data_dir("upload_samples/stud01_upload_test.zip")]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function uploadUploaded(ApiTester $I)
    {
        $I->sendPost(
            "/student/student-files/upload",
            ['taskID' => 5],
            ['file' => codecept_data_dir("upload_samples/stud01_upload_test.zip")]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA);
        $I->seeResponseContainsJson(
            [
                "name" => "stud01_upload_test.zip",
                "isAccepted" => "Updated",
                "translatedIsAccepted" => "Updated",
                "grade" => null,
                "notes" => "",
                "isVersionControlled" => 0,
                "graderName" => "",
                "errorMsg" => ""
            ]
        );
        $I->seeRecord(
            StudentFile::class,
            [
                "id" => 6,
                "name" => "stud01_upload_test.zip",
                "isAccepted" => "Updated",
            ]
        );
        $I->cantSeeFileFound("stud01.zip", Yii::$app->params["data_dir"] . "/uploadedfiles/5/stud01/");
        $I->seeFileFound("stud01_upload_test.zip", Yii::$app->params["data_dir"] . "/uploadedfiles/5/stud01/");
    }

    public function uploadAccepted(ApiTester $I)
    {
        $I->sendPost(
            "/student/student-files/upload",
            ['taskID' => 3],
            ['file' => codecept_data_dir("upload_samples/stud01_upload_test.zip")]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContainsJson(
            [
                'message' => 'Your solution was accepted!'
            ]
        );
    }

    public function uploadLateSubmission(ApiTester $I)
    {
        $I->sendPost(
            "/student/student-files/upload",
            ['taskID' => 2],
            ['file' => codecept_data_dir("upload_samples/stud01_upload_test.zip")]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA);
        $I->seeResponseContainsJson(
            [
                "name" => "stud01_upload_test.zip",
                "isAccepted" => "Updated",
                "translatedIsAccepted" => "Updated",
                "grade" => 4,
                "notes" => "",
                "isVersionControlled" => 0,
                "graderName" => "Teacher Two",
                "errorMsg" => ""
            ]
        );
        $I->seeRecord(
            StudentFile::class,
            [
                "id" => 1,
                "name" => "stud01_upload_test.zip",
                "isAccepted" => "Updated",
                "grade" => 4
            ]
        );
        $I->cantSeeFileFound("stud01.zip", Yii::$app->params["data_dir"] . "/uploadedfiles/2/stud01/");
        $I->seeFileFound("stud01_upload_test.zip", Yii::$app->params["data_dir"] . "/uploadedfiles/2/stud01/");
    }

    public function uploadNew(ApiTester $I)
    {
        $I->sendPost(
            "/student/student-files/upload",
            ['taskID' => 9],
            ['file' => codecept_data_dir("upload_samples/stud01_upload_test.zip")]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA);
        $I->seeResponseContainsJson(
            [
                "name" => "stud01_upload_test.zip",
                "isAccepted" => "Uploaded",
                "translatedIsAccepted" => "Uploaded",
                "grade" => null,
                "notes" => "",
                "isVersionControlled" => 0,
                "graderName" => '',
                "errorMsg" => null
            ]
        );
        $I->seeRecord(
            StudentFile::class,
            [
                "taskID" => 9,
                "name" => "stud01_upload_test.zip",
                "isAccepted" => "Uploaded",
            ]
        );
        $I->seeFileFound("stud01_upload_test.zip", Yii::$app->params["data_dir"] . "/uploadedfiles/9/stud01/");
    }
}
