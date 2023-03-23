<?php

namespace unit;

use app\components\CanvasIntegration;
use app\components\codechecker\CodeCheckerResultNotifier;
use app\exceptions\CodeCheckerResultNotifierException;
use app\models\CodeCheckerResult;
use app\models\StudentFile;
use app\tests\unit\fixtures\CodeCheckerResultFixture;
use app\tests\unit\fixtures\StudentFilesFixture;
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
            'studentfiles' => [
                'class' => StudentFilesFixture::class,
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
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 2]);
        $studentFile->canvasID = 1;

        $canvasMock = $this->createMock(CanvasIntegration::class);
        $canvasMock->expects($this->never())->method('uploadCodeCheckerResultToCanvas');
        Yii::$container->set(CanvasIntegration::class, $canvasMock);

        $notifier = new CodeCheckerResultNotifier();
        $this->expectException(CodeCheckerResultNotifierException::class);
        $notifier->sendNotifications($studentFile);

        $this->tester->seeEmailIsSent(0);
    }

    public function testFileWithInProgressResult()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 6]);
        $studentFile->canvasID = 1;

        $canvasMock = $this->createMock(CanvasIntegration::class);
        $canvasMock->expects($this->never())->method('uploadCodeCheckerResultToCanvas');
        Yii::$container->set(CanvasIntegration::class, $canvasMock);

        $notifier = new CodeCheckerResultNotifier();
        $this->expectException(CodeCheckerResultNotifierException::class);
        $notifier->sendNotifications($studentFile);

        $this->tester->seeEmailIsSent(0);
    }

    public function testFileValidWithCanvas()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 6]);
        $studentFile->canvasID = 1;
        $studentFile->codeCheckerResult->status = CodeCheckerResult::STATUS_NO_ISSUES;

        $canvasMock = $this->createMock(CanvasIntegration::class);
        $canvasMock->expects($this->once())->method('uploadCodeCheckerResultToCanvas');
        Yii::$container->set(CanvasIntegration::class, $canvasMock);

        $notifier = new CodeCheckerResultNotifier();
        $notifier->sendNotifications($studentFile);

        $this->tester->seeEmailIsSent(1);
    }

    public function testFileValidWithoutCanvas()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 6]);
        $studentFile->canvasID = null;
        $studentFile->codeCheckerResult->status = CodeCheckerResult::STATUS_NO_ISSUES;

        $canvasMock = $this->createMock(CanvasIntegration::class);
        $canvasMock->expects($this->never())->method('uploadCodeCheckerResultToCanvas');
        Yii::$container->set(CanvasIntegration::class, $canvasMock);

        $notifier = new CodeCheckerResultNotifier();
        $notifier->sendNotifications($studentFile);

        $this->tester->seeEmailIsSent(1);
    }
}
