<?php

namespace app\tests\unit;

use app\models\Submission;
use app\tests\unit\fixtures\LogFixture;
use app\tests\unit\fixtures\SubmissionsFixture;
use app\tests\unit\fixtures\TaskFixture;

/**
 * Unit tests for the Submissions model.
 */
class SubmissionsTest extends \Codeception\Test\Unit
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
            'submission' => [
                'class' => SubmissionsFixture::class,
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
        $file = new Submission(
            [
                'name' => 'test.zip',
                'uploadTime' => date('Y-m-d H:i:s', strtotime('-5 minute')),
                'taskID' => 5001,
                'uploaderID' => 1000,
                'status' => Submission::STATUS_ACCEPTED,
                'isVersionControlled' => 0,
                'grade' => 4,
                'notes' => '',
                'graderID' => 1000,
                'errorMsg' => self::FULL_ERROR_MSG,
                'verified' => true,
            ]
        );

        $file->autoTesterStatus = Submission::AUTO_TESTER_STATUS_NOT_TESTED;
        $this->assertNull($file->safeErrorMsg);

        $file->autoTesterStatus = Submission::AUTO_TESTER_STATUS_LEGACY_FAILED;
        $this->assertEquals(self::FULL_ERROR_MSG, $file->safeErrorMsg);

        $file->autoTesterStatus = Submission::AUTO_TESTER_STATUS_COMPILATION_FAILED;
        $this->assertEquals('The solution didn\'t compile', $file->safeErrorMsg);


        $file->autoTesterStatus = Submission::AUTO_TESTER_STATUS_INITIATION_FAILED;
        $this->assertEquals('The testing environment could\'t be initialized', $file->safeErrorMsg);

        $file->autoTesterStatus = Submission::AUTO_TESTER_STATUS_EXECUTION_FAILED;
        $this->assertEquals('Some error happened executing the program', $file->safeErrorMsg);

        $file->autoTesterStatus = Submission::AUTO_TESTER_STATUS_TESTS_FAILED;
        $this->assertEquals('Your solution failed the tests', $file->safeErrorMsg);

        $file->autoTesterStatus = Submission::AUTO_TESTER_STATUS_PASSED;
        $this->assertEquals('Your solution passed the tests', $file->safeErrorMsg);

        $file->autoTesterStatus = Submission::AUTO_TESTER_STATUS_IN_PROGRESS;
        $this->assertEquals('Your solution is being tested', $file->safeErrorMsg);

        $this->expectException(\UnexpectedValueException::class);
        $file->autoTesterStatus = 'Invalid';
        $file->getSafeErrorMsg();
    }

    /**
     * Tests getSafeErrorMsg getter: showFullErrorMsg is enabled for the given task
     * @return void
     */
    public function testSafeErrorMsgShowFullEnabled()
    {
        $file = new Submission([
            'name' => 'test.zip',
            'uploadTime' => date('Y-m-d H:i:s', strtotime('-5 minute')),
            'taskID' => 5002,
            'uploaderID' => 1000,
            'status' => Submission::STATUS_ACCEPTED,
            'isVersionControlled' => 0,
            'grade' => 4,
            'notes' => '',
            'graderID' => 1000,
            'errorMsg' => self::FULL_ERROR_MSG,
            'verified' => true,
        ]);

        $file->autoTesterStatus = Submission::AUTO_TESTER_STATUS_NOT_TESTED;
        $this->assertNull($file->safeErrorMsg);

        $file->autoTesterStatus = Submission::AUTO_TESTER_STATUS_LEGACY_FAILED;
        $this->assertEquals(self::FULL_ERROR_MSG, $file->safeErrorMsg);

        $file->autoTesterStatus = Submission::AUTO_TESTER_STATUS_COMPILATION_FAILED;
        $this->assertEquals(self::FULL_ERROR_MSG, $file->safeErrorMsg);

        $file->autoTesterStatus = Submission::AUTO_TESTER_STATUS_INITIATION_FAILED;
        $this->assertEquals(self::FULL_ERROR_MSG, $file->safeErrorMsg);

        $file->autoTesterStatus = Submission::AUTO_TESTER_STATUS_EXECUTION_FAILED;
        $this->assertEquals(self::FULL_ERROR_MSG, $file->safeErrorMsg);

        $file->autoTesterStatus = Submission::AUTO_TESTER_STATUS_TESTS_FAILED;
        $this->assertEquals(self::FULL_ERROR_MSG, $file->safeErrorMsg);

        $file->autoTesterStatus = Submission::AUTO_TESTER_STATUS_PASSED;
        $this->assertEquals('Your solution passed the tests', $file->safeErrorMsg);

        $file->autoTesterStatus = Submission::AUTO_TESTER_STATUS_IN_PROGRESS;
        $this->assertEquals('Your solution is being tested', $file->safeErrorMsg);

        $this->expectException(\UnexpectedValueException::class);
        $file->autoTesterStatus = 'Invalid';
        $file->getSafeErrorMsg();
    }

    public function testValidateAutoTesterStatusPassed()
    {
        $file = new Submission(
            [
                'name' => 'test.zip',
                'uploadTime' => date('Y-m-d H:i:s', strtotime('-5 minute')),
                'taskID' => 5002,
                'uploaderID' => 1000,
                'status' => Submission::STATUS_PASSED,
                'autoTesterStatus' => Submission::AUTO_TESTER_STATUS_PASSED,
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
            Submission::AUTO_TESTER_STATUS_NOT_TESTED,
            Submission::AUTO_TESTER_STATUS_LEGACY_FAILED,
            Submission::AUTO_TESTER_STATUS_COMPILATION_FAILED,
            Submission::AUTO_TESTER_STATUS_INITIATION_FAILED,
            Submission::AUTO_TESTER_STATUS_EXECUTION_FAILED,
            Submission::AUTO_TESTER_STATUS_TESTS_FAILED,
            Submission::AUTO_TESTER_STATUS_IN_PROGRESS,
        ];

        foreach ($invalidStatusValues as $value) {
            $file->autoTesterStatus = $value;
            $this->assertFalse($file->validate());
        }
    }

    public function testValidateAutoTesterStatusFailed()
    {
        $file = new Submission(
            [
                'name' => 'test.zip',
                'uploadTime' => date('Y-m-d H:i:s', strtotime('-5 minute')),
                'taskID' => 5002,
                'uploaderID' => 1000,
                'status' => Submission::STATUS_FAILED,
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
            Submission::AUTO_TESTER_STATUS_LEGACY_FAILED,
            Submission::AUTO_TESTER_STATUS_INITIATION_FAILED,
            Submission::AUTO_TESTER_STATUS_COMPILATION_FAILED,
            Submission::AUTO_TESTER_STATUS_EXECUTION_FAILED,
            Submission::AUTO_TESTER_STATUS_TESTS_FAILED,
        ];

        foreach ($validStatusValues as $value) {
            $file->autoTesterStatus = $value;
            $this->assertTrue($file->validate());
        }

        // Test invalid cases
        $invalidStatusValues = [
            Submission::AUTO_TESTER_STATUS_NOT_TESTED,
            Submission::AUTO_TESTER_STATUS_PASSED,
            Submission::AUTO_TESTER_STATUS_IN_PROGRESS,
        ];

        foreach ($invalidStatusValues as $value) {
            $file->autoTesterStatus = $value;
            $this->assertFalse($file->validate());
        }
    }

    public function testValidateAutoTesterStatusInProgress()
    {
        $file = new Submission(
            [
                'name' => 'test.zip',
                'uploadTime' => date('Y-m-d H:i:s', strtotime('-5 minute')),
                'taskID' => 5002,
                'uploaderID' => 1000,
                'status' => Submission::STATUS_UPLOADED,
                'autoTesterStatus' => Submission::AUTO_TESTER_STATUS_IN_PROGRESS,
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
            Submission::STATUS_ACCEPTED,
            Submission::STATUS_FAILED,
            Submission::STATUS_LATE_SUBMISSION,
            Submission::STATUS_PASSED,
            Submission::STATUS_REJECTED,
        ];

        foreach ($invalidStatusValues as $value) {
            $file->status = $value;
            $this->assertFalse($file->validate());
        }
    }

    /**
     * Tests is getIpAddresses getter.
     * @return void
     */
    public function testGetIpAddresses()
    {
        $file = Submission::findOne(16);
        $ipAddresses = $file->ipAddresses;
        // It should return all addresses only once
        $this->assertcount(3, $ipAddresses);
        $this->assertContains('192.168.1.1', $ipAddresses);
        $this->assertContains('192.168.1.2', $ipAddresses);
        $this->assertContains('192.168.1.3', $ipAddresses);
    }
}
