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

class InstructorStudentFilesCest
{
    public const STUDENT_FILES_SCHEMA = [
        'id' => 'integer',
        'name' => 'string',
        'uploadTime' => 'string',
        'uploadCount' => 'integer',
        'isAccepted' => 'string',
        'grade' => 'integer|string|null',
        'notes' => 'string |null',
        'isVersionControlled' => 'integer',
        'translatedIsAccepted' => 'string',
        'graderName' => 'string|null',
        'errorMsg' => 'string|null',
        'taskID' => 'integer',
        'groupID' => 'integer',
        'gitRepo' => 'string|null',
        'uploaderID' => 'integer'
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
        $I->amBearerAuthenticated("TEACH2;VALID");
        Yii::$app->language = 'en-US';
    }

    public function _after(ApiTester $I)
    {
        $I->deleteDir(Yii::$app->params['data_dir']);
    }

    public function listForTaskNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/list-for-task", ['taskID' => 0]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function listForTaskWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/list-for-task", ['taskID' => 5004]);
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function listForTask(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/list-for-task", ['taskID' => 5001]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA, "$.[*]");

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
        $I->sendGet("/instructor/student-files/export-spreadsheet", ['taskID' => 0, 'format' => 'csv']);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function exportSpreadsheettWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/export-spreadsheet", ['taskID' => 5004, 'format' => 'csv']);
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function exportSpreadsheetInvalidFormat(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/export-spreadsheet", ['taskID' => 5001, 'format' => 'invalid']);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function exportSpreadsheetXls(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/export-spreadsheet", ['taskID' => 5001, 'format' => 'xls']);
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function exportSpreadsheetCsv(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/export-spreadsheet", ['taskID' => 5001, 'format' => 'csv']);
        $I->seeResponseCodeIs(HttpCode::OK);
        // Contains headers
        $I->seeResponseContains('"Name","NEPTUN","Upload Time","Is Accepted","Grade","Grade","Notes","Graded By"');
        // Contains correct students
        $I->seeResponseContains('STUD02');
        $I->seeResponseContains('STUD02');
        $I->seeResponseContains('STUD03');
        $I->cantSeeResponseContains('STUD04');
        $I->cantSeeResponseContains('STUD05');
    }

    public function listForStudentStudentNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/list-for-student", ['groupID' => 0, 'uploaderID' => 1001]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function listForStudentTaskNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/list-for-student", ['groupID' => 2000, 'uploaderID' => 0]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function listForStudentWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/list-for-student", ['groupID' => 2007, 'uploaderID' => 1001]);
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function listForStudent(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/list-for-student", ['groupID' => 2000, 'uploaderID' => 1001]);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA, "$.[*]");

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
        $I->sendGet("/instructor/student-files/0");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function viewWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/6");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function view(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/1");
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(self::STUDENT_FILES_SCHEMA);

        $I->seeResponseContainsJson(
            [
                'id' => 1,
                'name' => 'stud01.zip',
                'isAccepted' => StudentFile::IS_ACCEPTED_LATE_SUBMISSION,
                'translatedIsAccepted' => 'Late Submission',
                'grade' => '4',
                'notes' => '',
                'isVersionControlled' => '0',
                'graderName' => 'Teacher Two',
                'errorMsg' => 'FULL_ERROR_MESSAGE',
                'taskID' => 5001,
                'groupID' => 2000,
                'uploaderID' => 1001,
                'gitRepo' => null,
                'uploadCount' => 1,
            ]
        );
    }

    public function updateNotFound(ApiTester $I)
    {
        $I->sendPatch(
            "/instructor/student-files/0",
            [
                'isAccepted' => StudentFile::IS_ACCEPTED_ACCEPTED,
                'grade' => 5,
                'notes' => 'Note'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function updateWithoutPermission(ApiTester $I)
    {
        $I->sendPatch(
            "/instructor/student-files/6",
            [
                'isAccepted' => StudentFile::IS_ACCEPTED_ACCEPTED,
                'grade' => 5,
                'notes' => 'Note'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function updateFromPreviousSemester(ApiTester $I)
    {
        $I->sendPatch(
            "/instructor/student-files/7",
            [
                'isAccepted' => StudentFile::IS_ACCEPTED_ACCEPTED,
                'grade' => 5,
                'notes' => 'Note'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function updateInvalid(ApiTester $I)
    {
        $I->sendPatch(
            "/instructor/student-files/1",
            [
                'isAccepted' => 'Invalid',
            ]
        );
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType(['string'], '$.[*]');
    }

    public function updateValid(ApiTester $I)
    {
        $I->sendPatch(
            "/instructor/student-files/1",
            [
                'isAccepted' => StudentFile::IS_ACCEPTED_ACCEPTED,
                'grade' => 5,
                'notes' => 'Note'
            ]
        );
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseContainsJson(
            [
                'id' => 1,
                'name' => 'stud01.zip',
                'isAccepted' => StudentFile::IS_ACCEPTED_ACCEPTED,
                'translatedIsAccepted' => 'Accepted',
                'isVersionControlled' => 0,
                'grade' => '5',
                'notes' => 'Note',
                'graderName' => 'Teacher Two',
                'gitRepo' => null,
                'errorMsg' => 'FULL_ERROR_MESSAGE',
                'uploadCount' => 1,
            ]
        );

        $I->seeRecord(
            StudentFile::class,
            [
                'id' => 1,
                'isAccepted' => StudentFile::IS_ACCEPTED_ACCEPTED,
                'grade' => 5,
                'notes' => 'Note'
            ]
        );

        $I->seeEmailIsSent(1);
    }


    public function downloadNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/0/download");
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function downloadWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/6/download");
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function download(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/1/download");
        $I->seeResponseCodeIs(HttpCode::OK);

        $I->openFile(Yii::$app->params['data_dir'] . "/uploadedfiles/5001/stud01/stud01.zip");
        $I->seeFileContentsEqual($I->grabResponse());
    }

    public function downloadAllFilesNotFound(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/download-all-files", ['taskID' => 0, 'onlyUngraded' => false]);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function downloadAllFilesWithoutPermission(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/download-all-files", ['taskID' => 5004, 'onlyUngraded' => false]);
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function downloadAllFiles(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/download-all-files", ['taskID' => 5001, 'onlyUngraded' => false]);
        $I->seeResponseCodeIs(HttpCode::OK);

        $zipPath = Yii::$app->params["data_dir"] . "/tmp/";
        if (!file_exists($zipPath)) {
            mkdir($zipPath, 0755, true);
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
        $I->sendGet("/instructor/student-files/download-all-files", ['taskID' => 5001, 'onlyUngraded' => true]);
        $I->seeResponseCodeIs(HttpCode::OK);

        $zipPath = Yii::$app->params["data_dir"] . "/tmp/";
        if (!file_exists($zipPath)) {
            mkdir($zipPath, 0755, true);
        }
        $I->writeToFile($zipPath . "codecept.zip", $I->grabResponse());
        $zip = new \ZipArchive();
        $zip->open($zipPath . "codecept.zip");
        $zip->extractTo($zipPath);
        $zip->close();

        $I->cantSeeFileFound("STUD01.zip", $zipPath);
        $I->cantSeeFileFound("STUD02.zip", $zipPath);
        $I->seeFileFound("STUD03.zip", $zipPath);
    }

    public function downloadAllEmpty(ApiTester $I)
    {
        $I->sendGet("/instructor/student-files/download-all-files", ['taskID' => 5003, 'onlyUngraded' => false]);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }
}
