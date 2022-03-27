<?php

namespace app\tests\unit;

use app\components\plagiarism\MossPlagiarismFinder;
use app\tests\unit\fixtures\PlagiarismFixture;
use app\tests\unit\fixtures\StudentFilesFixture;
use Yii;

/**
 * Unit tests for the MossPlagiarismFinder component.
 */
class MossPlagiarismFinderTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /** @var string[] The arguments Moss::addByWildcard() got */
    private $byWildcardArgs = [];
    /** @var string[] The arguments Moss::addBaseFile() got */
    private $baseFileArgs = [];

    protected function _before()
    {
        $this->byWildcardArgs = $this->baseFileArgs = [];
        $this->tester->deleteDir(Yii::$app->basePath . '/' . Yii::$app->params['data_dir']);
        $this->tester->copyDir(codecept_data_dir('appdata_samples'), Yii::$app->basePath . '/' . Yii::$app->params['data_dir']);
    }

    public function _after()
    {
        $this->tester->deleteDir(Yii::$app->basePath . '/' . Yii::$app->params['data_dir']);
    }

    public function _fixtures()
    {
        return [
            'plagiarisms' => [
                'class' => PlagiarismFixture::class,
            ],
            'studentfiles' => [
                'class' => StudentFilesFixture::class,
            ],
        ];
    }

    private function getFinder(string $fixtureIndex): MossPlagiarismFinder
    {
        $plagiarismId = $this->tester->grabFixture('plagiarisms', $fixtureIndex)->id;
        return new TestableMossPlagiarismFinder($plagiarismId, $this, $this->byWildcardArgs, $this->baseFileArgs);
    }

    public function testBasic()
    {
        $finder = $this->getFinder('plagiarism6');
        $finder->start();
        $this->assertEquals(['*/*/*.c', '*/*/*.txt', '*/*/*.md'], $this->byWildcardArgs);
        $this->assertEmpty($this->baseFileArgs);
    }

    public function testEmpty()
    {
        $finder = $this->getFinder('plagiarism5');
        $this->expectExceptionMessage('Submissions contain no supported file formats.');
        $finder->start();
    }

    public function testWithBaseFile()
    {
        $finder = $this->getFinder('plagiarism7');
        $finder->start();
        $this->assertEquals(['*/*/*.c', '*/*/*.txt', '*/*/*.md'], $this->byWildcardArgs);
        $baseFileIDs = explode(',', $this->tester->grabFixture('plagiarisms', 'plagiarism7')->baseFileIDs);
        $baseFileNames = array_map(static fn (string $id) => "basefiles/$id", $baseFileIDs);
        $this->assertEquals($baseFileNames, $this->baseFileArgs);
    }
}
