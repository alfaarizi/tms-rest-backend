<?php

namespace unit;

use app\components\docker\EvaluatorTarBuilder;
use app\exceptions\EvaluatorTarBuilderException;
use app\tests\unit\fixtures\InstructorFilesFixture;
use Yii;

class EvaluatorTarBuilderTest extends \Codeception\Test\Unit
{
    protected \UnitTester $tester;
    private EvaluatorTarBuilder $builder;
    private string $basePath;

    public function _fixtures(): array
    {
        return [
            'instructorfiles' => [
                'class' => InstructorFilesFixture::class,
            ]
        ];
    }

    protected function _before()
    {
        $this->tester->copyDir(codecept_data_dir("appdata_samples"), Yii::getAlias("@appdata"));
        $this->basePath = Yii::getAlias("@appdata");
        $this->builder = new EvaluatorTarBuilder(
            $this->basePath . '/tmp',
            'test'
        );
    }

    protected function _after()
    {
        $this->tester->deleteDir(Yii::getAlias("@appdata"));
    }

    public function testWithSubmission()
    {
        $this->builder->withSubmission($this->basePath . '/uploadedfiles/5001/stud01/stud01.zip');
        $this->tester->assertFileExists($this->basePath . '/tmp/test/submission/solution.txt');
    }

    public function testWittInstructorTestFiles()
    {
        $this->builder->withInstructorTestFiles(5007);
        $this->tester->assertFileExists($this->basePath . '/tmp/test/test_files/file2.txt');
        $this->tester->assertFileExists($this->basePath . '/tmp/test/test_files/file3.txt');
    }

    public function testWithTextFile()
    {
        $this->builder->withTextFile('file.txt', 'test');
        $this->tester->assertFileExists($this->basePath . '/tmp/test/file.txt');
    }

    public function testWithTextFileEmptySkip()
    {
        $this->builder->withTextFile('file.txt', null, true);
        $this->tester->assertFileNotExists($this->basePath . '/tmp/test/file.txt');
    }

    public function testWithTextFileEmpty()
    {
        $this->expectException(EvaluatorTarBuilderException::class);
        $this->builder->withTextFile('file.txt', null, false);
    }

    public function testBuildTar()
    {
        $this->builder->withTextFile('file.txt', 'test');
        $this->tester->assertFileExists($this->builder->buildTar());
    }

    public function testCleanup()
    {
        $this->builder->withTextFile('file.txt', 'test');
        $this->builder->buildTar();

        $this->tester->assertFileExists($this->basePath . '/tmp/test');
        $this->tester->assertFileExists($this->basePath . '/tmp/test.tar');

        $this->builder->cleanup();

        $this->tester->assertFileNotExists($this->basePath . '/tmp/test');
        $this->tester->assertFileNotExists($this->basePath . '/tmp/test.tar');
    }
}
