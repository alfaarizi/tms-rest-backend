<?php

namespace app\tests\unit;

use app\models\StudentFile;
use app\tests\unit\fixtures\LogFixture;
use app\tests\unit\fixtures\StudentFilesFixture;
use app\tests\unit\fixtures\TaskFixture;

/**
 * Unit tests for the StudentFile model.
 */
class StudentFileTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    private const FULL_ERROR_MSG = 'FULL_ERROR_MSG';

    public function _fixtures()
    {
        return [
            'tasks' => [
                'class' => TaskFixture::class,
            ],
            'studentfiles' => [
                'class' => StudentFilesFixture::class,
            ],
            'logs' => [
                'class' => LogFixture::class,
            ],
        ];
    }

    /**
     * Tests getSafeErrorMsg getter: showFullErrorMsg is disabled for the given task
     * @return void
     */
    public function testSafeErrorMsgShowFullDisabled()
    {
        $file = new StudentFile(
            [
                'name' => 'test.zip',
                'uploadTime' => date('Y-m-d H:i:s', strtotime('-5 minute')),
                'taskID' => 5001,
                'uploaderID' => 1000,
                'isAccepted' => StudentFile::IS_ACCEPTED_ACCEPTED,
                'isVersionControlled' => 0,
                'grade' => 4,
                'notes' => '',
                'graderID' => 1000,
                'errorMsg' => self::FULL_ERROR_MSG,
                'verified' => true,
            ]
        );

        $file->evaluatorStatus = StudentFile::EVALUATOR_STATUS_NOT_TESTED;
        $this->assertNull($file->safeErrorMsg);

        $file->evaluatorStatus = StudentFile::EVALUATOR_STATUS_LEGACY_FAILED;
        $this->assertEquals(self::FULL_ERROR_MSG, $file->safeErrorMsg);

        $file->evaluatorStatus = StudentFile::EVALUATOR_STATUS_COMPILATION_FAILED;
        $this->assertEquals('The solution didn\'t compile', $file->safeErrorMsg);

        $file->evaluatorStatus = StudentFile::EVALUATOR_STATUS_EXECUTION_FAILED;
        $this->assertEquals('Some error happened executing the program', $file->safeErrorMsg);

        $file->evaluatorStatus = StudentFile::EVALUATOR_STATUS_TESTS_FAILED;
        $this->assertEquals('Your solution failed the tests', $file->safeErrorMsg);

        $file->evaluatorStatus = StudentFile::EVALUATOR_STATUS_PASSED;
        $this->assertEquals('Your solution passed the tests', $file->safeErrorMsg);

        $file->evaluatorStatus = StudentFile::EVALUATOR_STATUS_IN_PROGRESS;
        $this->assertEquals('Your solution is being tested', $file->safeErrorMsg);

        $this->expectException(\UnexpectedValueException::class);
        $file->evaluatorStatus = 'Invalid';
        $file->getSafeErrorMsg();
    }

    /**
     * Tests getSafeErrorMsg getter: showFullErrorMsg is enabled for the given task
     * @return void
     */
    public function testSafeErrorMsgShowFullEnabled()
    {
        $file = new StudentFile([
            'name' => 'test.zip',
            'uploadTime' => date('Y-m-d H:i:s', strtotime('-5 minute')),
            'taskID' => 5002,
            'uploaderID' => 1000,
            'isAccepted' => StudentFile::IS_ACCEPTED_ACCEPTED,
            'isVersionControlled' => 0,
            'grade' => 4,
            'notes' => '',
            'graderID' => 1000,
            'errorMsg' => self::FULL_ERROR_MSG,
            'verified' => true,
        ]);

        $file->evaluatorStatus = StudentFile::EVALUATOR_STATUS_NOT_TESTED;
        $this->assertNull($file->safeErrorMsg);

        $file->evaluatorStatus = StudentFile::EVALUATOR_STATUS_LEGACY_FAILED;
        $this->assertEquals(self::FULL_ERROR_MSG, $file->safeErrorMsg);

        $file->evaluatorStatus = StudentFile::EVALUATOR_STATUS_COMPILATION_FAILED;
        $this->assertEquals(self::FULL_ERROR_MSG, $file->safeErrorMsg);

        $file->evaluatorStatus = StudentFile::EVALUATOR_STATUS_EXECUTION_FAILED;
        $this->assertEquals(self::FULL_ERROR_MSG, $file->safeErrorMsg);

        $file->evaluatorStatus = StudentFile::EVALUATOR_STATUS_TESTS_FAILED;
        $this->assertEquals(self::FULL_ERROR_MSG, $file->safeErrorMsg);

        $file->evaluatorStatus = StudentFile::EVALUATOR_STATUS_PASSED;
        $this->assertEquals('Your solution passed the tests', $file->safeErrorMsg);

        $file->evaluatorStatus = StudentFile::EVALUATOR_STATUS_IN_PROGRESS;
        $this->assertEquals('Your solution is being tested', $file->safeErrorMsg);

        $this->expectException(\UnexpectedValueException::class);
        $file->evaluatorStatus = 'Invalid';
        $file->getSafeErrorMsg();
    }

    public function testValidateEvaluatorStatusPassed()
    {
        $file = new StudentFile(
            [
                'name' => 'test.zip',
                'uploadTime' => date('Y-m-d H:i:s', strtotime('-5 minute')),
                'taskID' => 5002,
                'uploaderID' => 1000,
                'isAccepted' => StudentFile::IS_ACCEPTED_PASSED,
                'evaluatorStatus' => StudentFile::EVALUATOR_STATUS_PASSED,
                'isVersionControlled' => 0,
                'grade' => 4,
                'notes' => '',
                'graderID' => 1000,
                'errorMsg' => self::FULL_ERROR_MSG,
                'verified' => true,
            ]
        );
        // Test valid case
        $this->assertTrue($file->validate());

        // Test invalid cases
        $invalidStatusValues = [
            StudentFile::EVALUATOR_STATUS_NOT_TESTED,
            StudentFile::EVALUATOR_STATUS_LEGACY_FAILED,
            StudentFile::EVALUATOR_STATUS_COMPILATION_FAILED,
            StudentFile::EVALUATOR_STATUS_EXECUTION_FAILED,
            StudentFile::EVALUATOR_STATUS_TESTS_FAILED,
            StudentFile::EVALUATOR_STATUS_IN_PROGRESS,
        ];

        foreach ($invalidStatusValues as $value) {
            $file->evaluatorStatus = $value;
            $this->assertFalse($file->validate());
        }
    }

    public function testValidateEvaluatorStatusFailed()
    {
        $file = new StudentFile(
            [
                'name' => 'test.zip',
                'uploadTime' => date('Y-m-d H:i:s', strtotime('-5 minute')),
                'taskID' => 5002,
                'uploaderID' => 1000,
                'isAccepted' => StudentFile::IS_ACCEPTED_FAILED,
                'isVersionControlled' => 0,
                'grade' => 4,
                'notes' => '',
                'graderID' => 1000,
                'errorMsg' => self::FULL_ERROR_MSG,
                'verified' => true,
            ]
        );

        // Test valid cases
        $validStatusValues = [
            StudentFile::EVALUATOR_STATUS_LEGACY_FAILED,
            StudentFile::EVALUATOR_STATUS_COMPILATION_FAILED,
            StudentFile::EVALUATOR_STATUS_EXECUTION_FAILED,
            StudentFile::EVALUATOR_STATUS_TESTS_FAILED,
        ];

        foreach ($validStatusValues as $value) {
            $file->evaluatorStatus = $value;
            $this->assertTrue($file->validate());
        }

        // Test invalid cases
        $invalidStatusValues = [
            StudentFile::EVALUATOR_STATUS_NOT_TESTED,
            StudentFile::EVALUATOR_STATUS_PASSED,
            StudentFile::EVALUATOR_STATUS_IN_PROGRESS,
        ];

        foreach ($invalidStatusValues as $value) {
            $file->evaluatorStatus = $value;
            $this->assertFalse($file->validate());
        }
    }

    public function testValidateEvaluatorStatusInProgress()
    {
        $file = new StudentFile(
            [
                'name' => 'test.zip',
                'uploadTime' => date('Y-m-d H:i:s', strtotime('-5 minute')),
                'taskID' => 5002,
                'uploaderID' => 1000,
                'isAccepted' => StudentFile::IS_ACCEPTED_UPLOADED,
                'evaluatorStatus' => StudentFile::EVALUATOR_STATUS_IN_PROGRESS,
                'isVersionControlled' => 0,
                'grade' => 4,
                'notes' => '',
                'graderID' => 1000,
                'errorMsg' => self::FULL_ERROR_MSG,
                'verified' => true,
            ]
        );

        // Test valid case
        $this->assertTrue($file->validate());

        // Test invalid cases
        $invalidStatusValues = [
            StudentFile::IS_ACCEPTED_ACCEPTED,
            StudentFile::IS_ACCEPTED_FAILED,
            StudentFile::IS_ACCEPTED_LATE_SUBMISSION,
            StudentFile::IS_ACCEPTED_PASSED,
            StudentFile::IS_ACCEPTED_REJECTED,
        ];

        foreach ($invalidStatusValues as $value) {
            $file->isAccepted = $value;
            $this->assertFalse($file->validate());
        }
    }

    /**
     * Tests is getIpAddresses getter.
     * @return void
     */
    public function testGetIpAddresses()
    {
        $file = StudentFile::findOne(16);
        $ipAddresses = $file->ipAddresses;
        // It should return all addresses only once
        $this->assertcount(3, $ipAddresses);
        $this->assertContains('192.168.1.1', $ipAddresses);
        $this->assertContains('192.168.1.2', $ipAddresses);
        $this->assertContains('192.168.1.3', $ipAddresses);
    }
}
