<?php

namespace app\tests\unit;

use app\models\TestResult;
use app\tests\unit\fixtures\TestResultFixture;

/**
 * Unit tests for the TestResult model.
 */
class TestResultTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    private const FULL_ERROR_MSG = 'FULL_ERROR_MESSAGE';

    public function _fixtures()
    {
        return [
            'testresults' => [
                'class' => TestResultFixture::class,
            ],
        ];
    }

    /**
     * Tests getSafeErrorMsg getter: showFullErrorMsg is disabled for the given task
     * @return void
     */
    public function testSafeErrorMsgShowFullDisabled()
    {
        $result = TestResult::findOne(3);
        $this->assertEquals('Your solution failed the test', $result->safeErrorMsg);
    }

    /**
     * Tests getSafeErrorMsg getter: showFullErrorMsg is enabled for the given task
     * @return void
     */
    public function testSafeErrorMsgShowFullEnabled()
    {
        $result = TestResult::findOne(1);
        $this->assertEquals(self::FULL_ERROR_MSG, $result->safeErrorMsg);
    }
}
