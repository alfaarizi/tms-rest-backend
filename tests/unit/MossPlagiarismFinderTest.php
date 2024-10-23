<?php

namespace app\tests\unit;

use app\components\plagiarism\Moss;
use app\components\plagiarism\MossDownloader;
use app\components\plagiarism\MossPlagiarismFinder;
use app\tests\unit\fixtures\MossPlagiarismFixture;
use app\tests\unit\fixtures\PlagiarismFixture;
use app\tests\unit\fixtures\SubmissionsFixture;
use Yii;

/**
 * Unit tests for the MossPlagiarismFinder component.
 */
class MossPlagiarismFinderTest extends \Codeception\Test\Unit
{
    protected \UnitTester $tester;

    /** @var string[] The arguments Moss::addByWildcard() got */
    private array $byWildcardArgs = [];
    /** @var string[] The arguments Moss::addBaseFile() got */
    private array $baseFileArgs = [];

    protected function _before()
    {
        $this->byWildcardArgs = $this->baseFileArgs = [];
        Yii::$container->set(Moss::class, fn () => $this->makeEmpty(Moss::class, [
            'getAllowedExtensions' => ['c'],
            'getExtensionLanguages' => ['c'],
            'getLanguageExtensions' => ['c'],
            'addByWildcard' => function ($path) {
                $this->byWildcardArgs[] = $path;
            },
            'addBaseFile' => function ($file) {
                $this->baseFileArgs[] = $file;
            },
        ]));
        Yii::$container->set(MossDownloader::class, fn () => $this->makeEmpty(MossDownloader::class));
        $this->tester->deleteDir(Yii::getAlias("@appdata"));
        $this->tester->copyDir(codecept_data_dir('appdata_samples'), Yii::getAlias("@appdata"));
    }

    public function _after()
    {
        $this->tester->deleteDir(Yii::getAlias("@appdata"));
    }

    public function _fixtures()
    {
        return [
            'plagiarisms' => [
                'class' => PlagiarismFixture::class,
            ],
            'plagiarisms_moss' => [
                'class' => MossPlagiarismFixture::class,
            ],
            'submission' => [
                'class' => SubmissionsFixture::class,
            ],
        ];
    }

    private function getFinder(string $fixtureIndex): MossPlagiarismFinder
    {
        $plagiarism = $this->tester->grabFixture('plagiarisms', $fixtureIndex);
        return Yii::$container->get(MossPlagiarismFinder::class, [$plagiarism]);
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
