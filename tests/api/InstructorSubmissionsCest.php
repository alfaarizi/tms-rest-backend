<?php

namespace app\tests\api;

use ApiTester;
use app\tests\DateFormat;
use Helper\Api;
use Yii;
use app\models\Submission;
use app\tests\unit\fixtures\AccessTokenFixture;
use app\tests\unit\fixtures\SubmissionsFixture;
use app\tests\unit\fixtures\SubscriptionFixture;
use app\tests\unit\fixtures\TaskFixture;
use app\tests\unit\fixtures\UserFixture;
use app\tests\unit\fixtures\CodeCompassInstanceFixture;
use app\tests\unit\fixtures\TestResultFixture;
use Codeception\Util\HttpCode;
use yii\helpers\FileHelper;

class InstructorSubmissionsCest
{
    public const SUBMISSIONS_SCHEMA = [
        'id' => 'integer',
        'name' => 'string|null',
        'uploadTime' => 'string|null',
        'uploadCount' => 'integer',
        'status' => 'string',
        'grade' => 'integer|string|null',
        'notes' => 'string |null',
        'isVersionControlled' => 'integer',
        'translatedStatus' => 'string',
        'graderName' => 'string|null',
        'errorMsg' => 'string|null',
        'taskID' => 'integer',
        'groupID' => 'integer',
        'gitRepo' => 'string|null',
        'uploaderID' => 'integer',
        'codeCompassID' => 'integer|null'
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
            'submissions' => [
                'class' => SubmissionsFixture::class
            ],
            'users' => [
                'class' => UserFixture::class
            ],
            'subscriptions' => [
                'class' => SubscriptionFixture::class
            ],
            'codecompassinstances' => [
                'class' => CodeCompassInstanceFixture::class
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
        $I->amBearerAuthenticated("TEACH2;VALID");
        Yii::$app->language = 'en-US';
    }

    public function _after(ApiTester $I)
    {
        $I->deleteDir(Yii::getAlias("@appdata"));
        $I->deleteDir(Yii::getAlias("@tmp"));
    }

    public function listForTaskNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/list-for-task", ['taskID' => 0]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function listForTaskWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/list-for-task", ['taskID' => 5004]);
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function listForTask(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/list-for-task", ['taskID' => 5001]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::SUBMISSIONS_SCHEMA, "$.[*]");

        $I->seeResponseContainsJson(['id' => 1]);
        $I->seeResponseContainsJson(['id' => 2]);
        $I->seeResponseContainsJson(['id' => 8]);

        $I->cantSeeResponseContainsJson(['id' => 3]);
        $I->cantSeeResponseContainsJson(['id' => 4]);
        $I->cantSeeResponseContainsJson(['id' => 5]);
        $I->cantSeeResponseContainsJson(['id' => 6]);
        $I->cantSeeResponseContainsJson(['id' => 7]);
    }

    public function exportSpreadsheetNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/export-spreadsheet", ['taskID' => 0, 'format' => 'csv']);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function exportSpreadsheetWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/export-spreadsheet", ['taskID' => 5004, 'format' => 'csv']);
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function exportSpreadsheetInvalidFormat(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/export-spreadsheet", ['taskID' => 5001, 'format' => 'invalid']);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function exportSpreadsheetXlsx(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/export-spreadsheet", ['taskID' => 5001, 'format' => 'xlsx']);
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function exportSpreadsheetCsv(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/export-spreadsheet", ['taskID' => 5001, 'format' => 'csv']);
        $I->seeResponseCodeIs(HttpCode::OK);
        // Contains headers
        $I->seeResponseContains('"Name","User code","Upload Time","Status","Grade","Grade","Notes","Graded By","IP addresses"');
        // Contains correct students
        $I->seeResponseContains('STUD02');
        $I->seeResponseContains('STUD02');
        $I->seeResponseContains('STUD03');
        $I->cantSeeResponseContains('STUD04');
        $I->cantSeeResponseContains('STUD05');
    }

    public function listForStudentStudentNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/list-for-student", ['groupID' => 0, 'uploaderID' => 1001]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function listForStudentTaskNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/list-for-student", ['groupID' => 2000, 'uploaderID' => 0]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function listForStudentWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/list-for-student", ['groupID' => 2007, 'uploaderID' => 1001]);
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function listForStudent(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/list-for-student", ['groupID' => 2000, 'uploaderID' => 1001]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::SUBMISSIONS_SCHEMA, "$.[*]");

        $I->seeResponseContainsJson(['id' => 1]);
        $I->seeResponseContainsJson(['id' => 3]);

        $I->cantSeeResponseContainsJson(['id' => 2]);
        $I->cantSeeResponseContainsJson(['id' => 4]);
        $I->cantSeeResponseContainsJson(['id' => 5]);
        $I->cantSeeResponseContainsJson(['id' => 6]);
        $I->cantSeeResponseContainsJson(['id' => 7]);
    }

    public function viewNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/0");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function viewWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/6");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function view(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/1");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::SUBMISSIONS_SCHEMA);

        $I->seeResponseContainsJson(
            [
                'id' => 1,
                'name' => 'stud01.zip',
                'status' => Submission::STATUS_REJECTED,
                'translatedStatus' => 'Rejected',
                'grade' => 4,
                'notes' => '',
                'isVersionControlled' => 0,
                'graderName' => 'Teacher Two',
                'errorMsg' => 'FULL_ERROR_MESSAGE',
                'taskID' => 5001,
                'groupID' => 2000,
                'uploaderID' => 1001,
                'gitRepo' => null,
                'uploadCount' => 0,
                'codeCompassID' => 1,
            ]
        );
    }

    public function updateNotFound(ApiTester $I)
    {
        $I->sendPatch(
            "/instructor/submissions/0",
            [
                'status' => Submission::STATUS_ACCEPTED,
                'grade' => 5,
                'notes' => 'Note'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function updateWithoutPermission(ApiTester $I)
    {
        $I->sendPatch(
            "/instructor/submissions/6",
            [
                'status' => Submission::STATUS_ACCEPTED,
                'grade' => 5,
                'notes' => 'Note'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function updateFromPreviousSemester(ApiTester $I)
    {
        $I->sendPatch(
            "/instructor/submissions/7",
            [
                'status' => Submission::STATUS_ACCEPTED,
                'grade' => 5,
                'notes' => 'Note'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function updateInvalid(ApiTester $I)
    {
        $I->sendPatch(
            "/instructor/submissions/1",
            [
                'status' => 'Invalid',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
    }

    public function updateValid(ApiTester $I)
    {
        $I->sendPatch(
            "/instructor/submissions/1",
            [
                'status' => Submission::STATUS_ACCEPTED,
                'grade' => 5,
                'notes' => 'Note'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseContainsJson(
            [
                'id' => 1,
                'name' => 'stud01.zip',
                'status' => Submission::STATUS_ACCEPTED,
                'translatedStatus' => 'Accepted',
                'isVersionControlled' => 0,
                'grade' => '5',
                'notes' => 'Note',
                'graderName' => 'Teacher Two',
                'gitRepo' => null,
                'errorMsg' => 'FULL_ERROR_MESSAGE',
                'uploadCount' => 0,
            ]
        );

        $I->seeRecord(
            Submission::class,
            [
                'id' => 1,
                'status' => Submission::STATUS_ACCEPTED,
                'grade' => 5,
                'notes' => 'Note'
            ]
        );

        $I->seeEmailIsSent(1);
    }

    public function updateInProgress(ApiTester $I)
    {
        $I->sendPatch(
            "/instructor/submissions/2",
            [
                'status' => Submission::STATUS_ACCEPTED,
                'grade' => 5,
                'notes' => 'Note'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseContainsJson(
            [
                'id' => 2,
                'name' => 'stud02.zip',
                'status' => Submission::STATUS_ACCEPTED,
                'translatedStatus' => 'Accepted',
                'isVersionControlled' => 0,
                'grade' => '5',
                'notes' => 'Note',
                'graderName' => 'Teacher Two',
                'gitRepo' => null,
                'errorMsg' => null,
                'uploadCount' => 1,
            ]
        );

        $I->seeRecord(
            Submission::class,
            [
                'id' => 2,
                'status' => Submission::STATUS_ACCEPTED,
                'autoTesterStatus' => Submission::AUTO_TESTER_STATUS_NOT_TESTED,
                'grade' => 5,
                'notes' => 'Note',
            ]
        );
    }

    public function setPersonalDeadline(ApiTester $I)
    {
        $personalDeadlineDate = new \DateTime('+1 day');
        $I->sendPatch(
            "/instructor/submissions/1/set-personal-deadline",
            [
                'personalDeadline' =>  $personalDeadlineDate->format(\DateTime::ATOM)
            ]
        );

        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseContainsJson(
            [
                'id' => 1,
                'name' => 'stud01.zip',
                'personalDeadline' => $personalDeadlineDate->format(\DateTime::ATOM),
            ]
        );

        $I->seeRecord(
            Submission::class,
            [
                'id' => 1,
                'personalDeadline' => $personalDeadlineDate->format(DateFormat::MYSQL),
            ]
        );
    }

    public function downloadNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/0/download");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function downloadWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/6/download");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function download(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/1/download");
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->openFile(Yii::getAlias("@appdata/uploadedfiles/5001/stud01/stud01.zip"));
        $I->seeFileContentsEqual($I->grabResponse());
    }

    public function downloadAllFilesNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/download-all-files", ['taskID' => 0, 'onlyUngraded' => false]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function downloadAllFilesWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/download-all-files", ['taskID' => 5004, 'onlyUngraded' => false]);
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function downloadAllFiles(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/download-all-files", ['taskID' => 5001, 'onlyUngraded' => false]);
        $I->seeResponseCodeIs(HttpCode::OK);

        $zipPath = Yii::getAlias("@tmp/");
        if (!file_exists($zipPath)) {
            FileHelper::createDirectory($zipPath, 0755, true);
        }
        $I->writeToFile($zipPath . "codecept.zip", $I->grabResponse());
        $zip = new \ZipArchive();
        $zip->open($zipPath . "codecept.zip");
        $zip->extractTo($zipPath);
        $zip->close();

        $I->seeFileFound("STUD01.zip", $zipPath);
        $I->seeFileFound("STUD02.zip", $zipPath);
        $I->seeFileFound("STUD03.zip", $zipPath);
    }

    public function downloadAllFilesOnlyUngraded(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/download-all-files", ['taskID' => 5001, 'onlyUngraded' => true]);
        $I->seeResponseCodeIs(HttpCode::OK);

        $zipPath = Yii::getAlias("@tmp/");
        if (!file_exists($zipPath)) {
            FileHelper::createDirectory($zipPath, 0755, true);
        }
        $I->writeToFile($zipPath . "codecept.zip", $I->grabResponse());
        $zip = new \ZipArchive();
        $zip->open($zipPath . "codecept.zip");
        $zip->extractTo($zipPath);
        $zip->close();

        $I->cantSeeFileFound("STUD01.zip", $zipPath);
        $I->seeFileFound("STUD02.zip", $zipPath);
        $I->seeFileFound("STUD03.zip", $zipPath);
    }

    public function downloadAllEmpty(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/download-all-files", ['taskID' => 5003, 'onlyUngraded' => false]);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function startCodeCompassNotFound(ApiTester $I)
    {
        $I->sendPost("/instructor/submissions/0/start-code-compass");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function startCodeCompassWithoutPermission(ApiTester $I)
    {
        $I->sendPost("/instructor/submissions/6/start-code-compass");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function startCodeCompassAlreadyStarted(ApiTester $I)
    {
        $I->sendPost("/instructor/submissions/1/start-code-compass");
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
    }

    public function startCodeCompassCurrentlyStarting(ApiTester $I)
    {
        $I->sendPost("/instructor/submissions/2/start-code-compass");
        $I->seeResponseCodeIs(HttpCode::CONFLICT);
    }

    public function stopCodeCompassNotFound(ApiTester $I)
    {
        $I->sendPost("/instructor/submissions/0/stop-code-compass");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function stopCodeCompassWithoutPermission(ApiTester $I)
    {
        $I->sendPost("/instructor/submissions/5/start-code-compass");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function stopCodeCompassCurrentlyStarting(ApiTester $I)
    {
        $I->sendPost("/instructor/submissions/2/stop-code-compass");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function stopCodeCompassNotRunning(ApiTester $I)
    {
        $I->sendPost("/instructor/submissions/3/stop-code-compass");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function viewTestResults(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/51/auto-tester-results");
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

    public function generateJWTInvalidSubmission(ApiTester $I)
    {
        $I->sendPost("/instructor/submissions/9999/jwt");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function generateJWTNotManagedSubmission(ApiTester $I)
    {
        $I->sendPost("/instructor/submissions/5/jwt");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function generateJWTValidPayload(ApiTester $I)
    {
        $submissionId = 1;
        $expectedPayload = [
            'submissionId' => $submissionId,
            'studentId' => 1001,
        ];

        $I->sendPost("/instructor/submissions/${submissionId}/jwt");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType(['token' => 'string']);

        $response = json_decode($I->grabResponse(), true);
        $jwtToken = $response['token'];

        $parts = explode('.', $jwtToken);
        if (count($parts) != 3) {
            $I->fail("Invalid JWT format: Expected 3 parts, got " . count($parts));
        }

        $decodedPayload = json_decode(base64_decode($parts[1]), true);

        $I->assertEquals($expectedPayload, $decodedPayload, "Decoded JWT payload does not match the expected payload.");
    }

    public function validateJWTMissingToken(ApiTester $I)
    {
        $I->sendGet("/instructor/submissions/jwt-validate", []);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function validateJWTValid(ApiTester $I)
    {
        $submissionId = 1;
        $expectedPayload = [
            'submissionId' => $submissionId,
            'studentId' => 1001,
        ];

        $I->sendPost("/instructor/submissions/${submissionId}/jwt");
        $I->seeResponseCodeIs(HttpCode::OK);
        $response = json_decode($I->grabResponse(), true);
        $jwtToken = $response['token'];

        $I->sendGet("/instructor/submissions/jwt-validate", ['token' => $jwtToken]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType([
            'success' => 'boolean',
            'payload' => 'array',
            'message' => 'string',
        ]);

        $I->seeResponseContainsJson(
            [
                'success' => true,
                'payload' => $expectedPayload,
            ]
        );
    }

    public function validateJWTInvalidSignature(ApiTester $I)
    {
        $submissionId = 1;

        $I->sendPost("/instructor/submissions/${submissionId}/jwt");
        $I->seeResponseCodeIs(HttpCode::OK);
        $response = json_decode($I->grabResponse(), true);
        $jwtToken = $response['token'];

        $parts = explode('.', $jwtToken);
        $parts[2] = str_repeat('x', strlen($parts[2])); //header.payload.xxxxxx
        $invalidJwtToken = implode('.', $parts);

        $I->sendGet("/instructor/submissions/jwt-validate", ['token' => $invalidJwtToken]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType([
            'success' => 'boolean',
            'message' => 'string',
        ]);

        $I->seeResponseContainsJson(
            [
                'success' => false,
            ]
        );
    }
}
