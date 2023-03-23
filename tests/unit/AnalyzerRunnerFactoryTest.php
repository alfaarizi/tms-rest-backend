<?php

namespace unit;

use app\components\codechecker\AnalyzerRunnerFactory;
use app\models\StudentFile;
use app\tests\unit\fixtures\StudentFilesFixture;
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
            'studentfiles' => [
                'class' => StudentFilesFixture::class,
            ],
            'task' => [
                'class' => TaskFixture::class,
            ],
        ];
    }

    public function testInvalidAnalyzer()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 1]);
        $studentFile->task->staticCodeAnalyzerTool = "unknown";

        $this->expectException(InvalidConfigException::class);

        AnalyzerRunnerFactory::createForStudentFile(Yii::$container, ['studentFile' => $studentFile], []);
    }

    public function testCodeChecker()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 1]);
        $studentFile->task->staticCodeAnalyzerTool = 'codechecker';

        $runner = AnalyzerRunnerFactory::createForStudentFile(Yii::$container, ['studentFile' => $studentFile], []);

        $this->assertEquals('app\components\codechecker\CodeCheckerRunner', get_class($runner));
    }

    public function testReportConverter()
    {
        $studentFile = $this->tester->grabRecord(StudentFile::class, ['id' => 1]);
        $studentFile->task->staticCodeAnalyzerTool = 'roslynator';

        $runner = AnalyzerRunnerFactory::createForStudentFile(Yii::$container, ['studentFile' => $studentFile], []);

        $this->assertEquals('app\components\codechecker\ReportConverterRunner', get_class($runner));
    }
}
