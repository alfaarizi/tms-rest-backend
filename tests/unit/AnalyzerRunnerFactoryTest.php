<?php

namespace app\tests\unit;

use app\components\codechecker\AnalyzerRunnerFactory;
use app\models\Submission;
use app\tests\unit\fixtures\SubmissionsFixture;
use app\tests\unit\fixtures\TaskFixture;
use Codeception\Test\Unit;
use yii\base\InvalidConfigException;
use Yii;

class AnalyzerRunnerFactoryTest extends Unit
{
    protected \UnitTester $tester;

    public function _fixtures(): array
    {
        return [
            'submission' => [
                'class' => SubmissionsFixture::class,
            ],
            'task' => [
                'class' => TaskFixture::class,
            ],
        ];
    }

    public function testInvalidAnalyzer()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 1]);
        $submission->task->staticCodeAnalyzerTool = "unknown";

        $this->expectException(InvalidConfigException::class);

        AnalyzerRunnerFactory::createForSubmission(Yii::$container, ['submission' => $submission], []);
    }

    public function testCodeChecker()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 1]);
        $submission->task->staticCodeAnalyzerTool = 'codechecker';

        $runner = AnalyzerRunnerFactory::createForSubmission(Yii::$container, ['submission' => $submission], []);

        $this->assertEquals('app\components\codechecker\CodeCheckerRunner', get_class($runner));
    }

    public function testReportConverter()
    {
        $submission = $this->tester->grabRecord(Submission::class, ['id' => 1]);
        $submission->task->staticCodeAnalyzerTool = 'roslynator';

        $runner = AnalyzerRunnerFactory::createForSubmission(Yii::$container, ['submission' => $submission], []);

        $this->assertEquals('app\components\codechecker\ReportConverterRunner', get_class($runner));
    }
}
