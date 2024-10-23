<?php

namespace app\tests\unit;

use app\components\CanvasIntegration;
use app\components\codechecker\CodeCheckerResultNotifier;
use app\exceptions\CodeCheckerResultNotifierException;
use app\models\CodeCheckerResult;
use app\models\Submission;
use app\tests\unit\fixtures\CodeCheckerResultFixture;
use app\tests\unit\fixtures\SubmissionsFixture;
use app\tests\unit\fixtures\SubscriptionFixture;
use app\tests\unit\fixtures\UserFixture;
use Codeception\Test\Unit;
use Yii;

class CodeCheckerResultNotifierTest extends Unit
{
    protected \UnitTester $tester;

    public function _fixtures(): array
    {
        return [
            'submission' => [
                'class' => SubmissionsFixture::class,
            ],
            'subscription' => [
                'class' => SubscriptionFixture::class,
            ],
            'user' => [
                'class' => UserFixture::class,
            ],
            'codecheckerresults' => [
                'class' => CodeCheckerResultFixture::class
            ]
        ];
    }

    public function testFileWithoutResult()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 2]);
        $submission->canvasID = 1;

        $canvasMock = $this->createMock(CanvasIntegration::class);
        $canvasMock->expects($this->never())->method('uploadCodeCheckerResultToCanvas');
        Yii::$container->set(CanvasIntegration::class, $canvasMock);

        $notifier = new CodeCheckerResultNotifier();
        $this->expectException(CodeCheckerResultNotifierException::class);
        $notifier->sendNotifications($submission);

        $this->tester->seeEmailIsSent(0);
    }

    public function testFileWithInProgressResult()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 6]);
        $submission->canvasID = 1;

        $canvasMock = $this->createMock(CanvasIntegration::class);
        $canvasMock->expects($this->never())->method('uploadCodeCheckerResultToCanvas');
        Yii::$container->set(CanvasIntegration::class, $canvasMock);

        $notifier = new CodeCheckerResultNotifier();
        $this->expectException(CodeCheckerResultNotifierException::class);
        $notifier->sendNotifications($submission);

        $this->tester->seeEmailIsSent(0);
    }

    public function testFileValidWithCanvas()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 6]);
        $submission->canvasID = 1;
        $submission->codeCheckerResult->status = CodeCheckerResult::STATUS_NO_ISSUES;

        $canvasMock = $this->createMock(CanvasIntegration::class);
        $canvasMock->expects($this->once())->method('uploadCodeCheckerResultToCanvas');
        Yii::$container->set(CanvasIntegration::class, $canvasMock);

        $notifier = new CodeCheckerResultNotifier();
        $notifier->sendNotifications($submission);

        $this->tester->seeEmailIsSent(1);
    }

    public function testFileValidWithoutCanvas()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 6]);
        $submission->canvasID = null;
        $submission->codeCheckerResult->status = CodeCheckerResult::STATUS_NO_ISSUES;

        $canvasMock = $this->createMock(CanvasIntegration::class);
        $canvasMock->expects($this->never())->method('uploadCodeCheckerResultToCanvas');
        Yii::$container->set(CanvasIntegration::class, $canvasMock);

        $notifier = new CodeCheckerResultNotifier();
        $notifier->sendNotifications($submission);

        $this->tester->seeEmailIsSent(1);
    }
}
