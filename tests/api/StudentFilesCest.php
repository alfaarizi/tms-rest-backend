<?php

namespace app\tests\api;

use ApiTester;
use Yii;
use app\models\StudentFile;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\StudentFilesFixture;
use app\tests\unit\fixtures\SubscriptionFixture;
use app\tests\unit\fixtures\TaskFixture;
use app\tests\unit\fixtures\UserFixture;
use app\tests\unit\fixtures\LogFixture;
use app\tests\unit\fixtures\TestResultFixture;
use Codeception\Util\HttpCode;

class StudentFilesCest
{
    public const STUDENT_FILES_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'uploadTime' => 'string',
        'uploadCount' => 'integer',
        'isAccepted' => 'string|null',
        'translatedIsAccepted' => 'string',
        'grade' => 'integer|null',
        'notes' => 'string|null',
        'isVersionControlled' => 'integer',
        'graderName' => 'string|null',
        'errorMsg' => 'string|null',
        'taskID' => 'integer',
    ];

    public const TEST_RESULT_SCHEMA = [
        'testCaseNr' => 'integer',
        'isPassed' => 'boolean',
        'errorMsg' => 'string|null',
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
            'logs' => [
                'class' => LogFixture::class
            ],
            'testresults' => [
                'class' => TestResultFixture::class
            ],
        ];
    }

    public function _before(ApiTester $I)
    {
        $I->deleteDir(Yii::getAlias("@appdata"));
        $I->copyDir(codecept_data_dir("appdata_samples"), Yii::getAlias("@appdata"));
        $I->amBearerAuthenticated("STUD01;VALID");
        Yii::$app->language = 'en-US';
    }

    public function _after(ApiTester $I)
    {
        $I->deleteDir(Yii::getAlias("@appdata"));
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

    public function viewWithoutFullErrorMessage(ApiTester $I)
    {
        $I->sendGet("/student/student-files/1");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA);

        $I->seeResponseContainsJson(
            [
                'id' => 1,
                'name' => 'stud01.zip',
                'isAccepted' => StudentFile::IS_ACCEPTED_LATE_SUBMISSION,
                'translatedIsAccepted' => 'Late Submission',
                'isVersionControlled' => 0,
                'grade' => 4,
                'notes' => '',
                'graderName' => 'Teacher Two',
                'errorMsg' => 'The solution didn\'t compile',
                'uploadCount' => 1,
                'verified' => true,
            ]
        );
    }

    public function viewWithFullErrorMessage(ApiTester $I)
    {
        $I->sendGet("/student/student-files/3");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA);

        $I->seeResponseContainsJson(
            [
                'id' => 3,
                'name' => 'stud01.zip',
                'isAccepted' => StudentFile::IS_ACCEPTED_ACCEPTED,
                'translatedIsAccepted' => 'Accepted',
                'isVersionControlled' => 0,
                'grade' => '5',
                'notes' => '',
                'graderName' => 'Teacher Two',
                'errorMsg' => 'FULL_ERROR_MESSAGE',
                'uploadCount' => 1,
                'verified' => true,
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

        $I->openFile(Yii::getAlias("@appdata/uploadedfiles/5001/stud01/stud01.zip"));
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
            ['taskID' => 5003],
            ['file' => codecept_data_dir("upload_samples/stud01_upload_test.zip")]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function uploadWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            "/student/student-files/upload",
            ['taskID' => 5007],
            ['file' => codecept_data_dir("upload_samples/stud01_upload_test.zip")]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function uploadExpired(ApiTester $I)
    {
        $I->sendPost(
            "/student/student-files/upload",
            ['taskID' => 5000],
            ['file' => codecept_data_dir("upload_samples/stud01_upload_test.zip")]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function uploadUploaded(ApiTester $I)
    {
        $I->sendPost(
            "/student/student-files/upload",
            ['taskID' => 5004],
            ['file' => codecept_data_dir("upload_samples/stud01_upload_test.zip")]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA);
        $I->seeResponseContainsJson(
            [
                "name" => "stud01_upload_test.zip",
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
                "translatedIsAccepted" => "Uploaded",
                "grade" => null,
                "notes" => "",
                "isVersionControlled" => 0,
                "graderName" => "",
                "errorMsg" => null,
                "uploadCount" => 2,
                "verified" => true,
            ]
        );
        $I->seeRecord(
            StudentFile::class,
            [
                "id" => 6,
                "name" => "stud01_upload_test.zip",
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
                "uploadCount" => 2,
            ]
        );
        $I->cantSeeFileFound("stud01.zip", Yii::getAlias("@appdata/uploadedfiles/5004/stud01/"));
        $I->seeFileFound("stud01_upload_test.zip", Yii::getAlias("@appdata/uploadedfiles/5004/stud01/"));
    }

    public function uploadAccepted(ApiTester $I)
    {
        $I->sendPost(
            "/student/student-files/upload",
            ['taskID' => 5002],
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
            ['taskID' => 5001],
            ['file' => codecept_data_dir("upload_samples/stud01_upload_test.zip")]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA);
        $I->seeResponseContainsJson(
            [
                "name" => "stud01_upload_test.zip",
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
                "translatedIsAccepted" => "Uploaded",
                "grade" => 4,
                "notes" => "",
                "isVersionControlled" => 0,
                "graderName" => "Teacher Two",
                "errorMsg" => null,
                "uploadCount" => 2,
                'verified' => true,
            ]
        );
        $I->seeRecord(
            StudentFile::class,
            [
                "id" => 1,
                "name" => "stud01_upload_test.zip",
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
                "grade" => 4,
                "uploadCount" => 2,
            ]
        );
        $I->cantSeeFileFound("stud01.zip", Yii::getAlias("@appdata/uploadedfiles/5001/stud01/"));
        $I->seeFileFound("stud01_upload_test.zip", Yii::getAlias("@appdata/uploadedfiles/5001/stud01/"));
    }

    public function uploadNew(ApiTester $I)
    {
        $I->sendPost(
            "/student/student-files/upload",
            ['taskID' => 5008],
            ['file' => codecept_data_dir("upload_samples/stud01_upload_test.zip")]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA);
        $I->seeResponseContainsJson(
            [
                "name" => "stud01_upload_test.zip",
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
                "translatedIsAccepted" => "Uploaded",
                "grade" => null,
                "notes" => "",
                "isVersionControlled" => 0,
                "graderName" => '',
                "errorMsg" => null,
                "uploadCount" => 1,
                "verified" => true,
            ]
        );
        $I->seeRecord(
            StudentFile::class,
            [
                "taskID" => 5008,
                "name" => "stud01_upload_test.zip",
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
            ]
        );
        $I->seeFileFound("stud01_upload_test.zip", Yii::getAlias("@appdata/uploadedfiles/5008/stud01/"));
    }

    public function uploadToPasswordProtectedTask(ApiTester $I)
    {
        $I->sendPost(
            "/student/student-files/upload",
            ['taskID' => 5010],
            ['file' => codecept_data_dir("upload_samples/stud01_upload_test.zip")]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA);

        $I->seeResponseContainsJson(
            [
                "name" => "stud01_upload_test.zip",
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
                "translatedIsAccepted" => "Uploaded",
                "grade" => null,
                "notes" => "",
                "isVersionControlled" => 0,
                "graderName" => "",
                "errorMsg" => null,
                "uploadCount" => 1,
                'verified' => false
            ]
        );
        $I->seeRecord(
            StudentFile::class,
            [
                "taskID" => 5010,
                "name" => "stud01_upload_test.zip",
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
                "uploadCount" => 1,
            ]
        );
        $I->seeFileFound("stud01_upload_test.zip", Yii::getAlias("@appdata/uploadedfiles/5010/stud01/"));
    }

    public function reuploadUnverifiedSolution(ApiTester $I)
    {
        $I->sendPost(
            "/student/student-files/upload",
            ['taskID' => 5011],
            ['file' => codecept_data_dir("upload_samples/stud01_upload_test.zip")]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA);

        $I->seeResponseContainsJson(
            [
                "name" => "stud01_upload_test.zip",
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
                "translatedIsAccepted" => "Uploaded",
                "grade" => null,
                "notes" => "",
                "isVersionControlled" => 0,
                "graderName" => "",
                "errorMsg" => null,
                "uploadCount" => 2,
                "verified" => false,
            ]
        );
        $I->seeRecord(
            StudentFile::class,
            [
                "id" => 12,
                "taskID" => 5011,
                "name" => "stud01_upload_test.zip",
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
                "uploadCount" => 2,
                "verified" => false,
            ]
        );
        $I->seeFileFound("stud01_upload_test.zip", Yii::getAlias("@appdata/uploadedfiles/5011/stud01/"));
    }

    public function reuploadVerifiedSolution(ApiTester $I)
    {
        $I->sendPost(
            "/student/student-files/upload",
            ['taskID' => 5012],
            ['file' => codecept_data_dir("upload_samples/stud01_upload_test.zip")]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA);

        $I->seeResponseContainsJson(
            [
                "name" => "stud01_upload_test.zip",
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
                "translatedIsAccepted" => "Uploaded",
                "grade" => null,
                "notes" => "",
                "isVersionControlled" => 0,
                "graderName" => "",
                "errorMsg" => null,
                "uploadCount" => 2,
                "verified" => false,
            ]
        );
        $I->seeRecord(
            StudentFile::class,
            [
                "id" => 13,
                "taskID" => 5012,
                "name" => "stud01_upload_test.zip",
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
                "uploadCount" => 2,
                "verified" => false,
            ]
        );
        $I->seeFileFound("stud01_upload_test.zip", Yii::getAlias("@appdata/uploadedfiles/5012/stud01/"));
    }

    public function verifySolution(ApiTester $I)
    {
        $I->sendPost(
            '/student/student-files/verify',
            [
                'id' => 12,
                'password' => 'password',
                'disableIpCheck' => false,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA);

        $I->seeResponseContainsJson(
            [
                "id" => 12,
                "name" => "stud01.zip",
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
                "translatedIsAccepted" => "Uploaded",
                "grade" => null,
                "notes" => "",
                "isVersionControlled" => 0,
                "graderName" => "",
                "errorMsg" => null,
                "uploadCount" => 1,
                "verified" => true,
            ]
        );
        $I->seeRecord(
            StudentFile::class,
            [
                "id" => 12,
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
                "verified" => true,
            ]
        );
    }

    public function verifySolutionAlreadyVerified(ApiTester $I)
    {
        $I->sendPost(
            '/student/student-files/verify',
            [
                'id' => 13,
                'password' => 'password',
                'disableIpCheck' => false,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function verifySolutionInvalidRequest(ApiTester $I)
    {
        $I->sendPost(
            '/student/student-files/verify',
            [
                'password' => 'password'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
    }

    public function verifySolutionWrongPassword(ApiTester $I)
    {
        $I->sendPost(
            '/student/student-files/verify',
            [
                'id' => 12,
                'password' => 'wrong',
                'disableIpCheck' => false,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
    }

    public function verifyWithoutPermission(ApiTester $I)
    {
        $I->sendPost(
            '/student/student-files/verify',
            [
                'id' => 2,
                'password' => 'password',
                'disableIpCheck' => false,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function verifyFileNotFound(ApiTester $I)
    {
        $I->sendPost(
            '/student/student-files/verify',
            [
                'id' => 0,
                'password' => 'password',
                'disableIpCheck' => false,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }


    public function verifyDifferentIp(ApiTester $I)
    {
        $I->sendPost(
            '/student/student-files/verify',
            [
                'id' => 15,
                'password' => 'password',
                'disableIpCheck' => false,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
    }

    public function verifyDifferentIpDisableCheck(ApiTester $I)
    {
        $I->sendPost(
            '/student/student-files/verify',
            [
                'id' => 15,
                'password' => 'password',
                'disableIpCheck' => true,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA);

        $I->seeResponseContainsJson(
            [
                "id" => 15,
                "name" => "stud01.zip",
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
                "translatedIsAccepted" => "Uploaded",
                "grade" => null,
                "notes" => "",
                "isVersionControlled" => 0,
                "graderName" => "",
                "errorMsg" => null,
                "uploadCount" => 1,
                "verified" => true,
            ]
        );
        $I->seeRecord(
            StudentFile::class,
            [
                "id" => 15,
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
                "verified" => true,
            ]
        );
    }

    public function verifyMultipleIpAddresses(ApiTester $I)
    {
        $I->sendPost(
            '/student/student-files/verify',
            [
                'id' => 16,
                'password' => 'password',
                'disableIpCheck' => false,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
    }

    public function verifyMultipleIpAddressesDisableCheck(ApiTester $I)
    {
        $I->sendPost(
            '/student/student-files/verify',
            [
                'id' => 16,
                'password' => 'password',
                'disableIpCheck' => true,
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA);

        $I->seeResponseContainsJson(
            [
                "id" => 16,
                "name" => "stud01.zip",
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
                "translatedIsAccepted" => "Uploaded",
                "grade" => null,
                "notes" => "",
                "isVersionControlled" => 0,
                "graderName" => "",
                "errorMsg" => null,
                "uploadCount" => 1,
                "verified" => true,
            ]
        );
        $I->seeRecord(
            StudentFile::class,
            [
                "id" => 16,
                "isAccepted" => StudentFile::IS_ACCEPTED_UPLOADED,
                "verified" => true,
            ]
        );
    }

    public function viewTestResultsWithFullErrorMessage(ApiTester $I)
    {
        $I->sendGet("/student/student-files/51/auto-tester-results");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TEST_RESULT_SCHEMA);

        $I->seeResponseContainsJson(
            [
                'testCaseNr' => 1,
                'isPassed' => false,
                'errorMsg' => 'FULL_ERROR_MESSAGE',
            ]
        );

        $I->seeResponseContainsJson(
            [
                'testCaseNr' => 2,
                'isPassed' => true,
                'errorMsg' => null,
            ]
        );
    }

    public function viewTestResultsWithoutFullErrorMessage(ApiTester $I)
    {
        $I->sendGet("/student/student-files/52/auto-tester-results");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::TEST_RESULT_SCHEMA);

        $I->seeResponseContainsJson(
            [
                'testCaseNr' => 1,
                'isPassed' => false,
                'errorMsg' => 'Your solution failed the test',
            ]
        );

        $I->seeResponseContainsJson(
            [
                'testCaseNr' => 2,
                'isPassed' => true,
                'errorMsg' => '',
            ]
        );
    }
}
