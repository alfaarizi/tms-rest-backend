<?php

namespace app\tests\unit;

use app\tests\unit\fixtures\JPlagPlagiarismFixture;
use app\tests\unit\fixtures\PlagiarismFixture;
use app\tests\unit\fixtures\StudentFilesFixture;
use Yii;

/**
 * Unit tests for the JPlagPlagiarismFinder component.
 */
class JPlagPlagiarismFinderTest extends \Codeception\Test\Unit
{
    protected \UnitTester $tester;

    private string $appdata_dir;

    protected function _before()
    {
        $this->appdata_dir = Yii::getAlias("@appdata");
        $this->tester->deleteDir(Yii::getAlias("@appdata"));
        $this->tester->copyDir(codecept_data_dir('appdata_samples'), Yii::getAlias("@appdata"));
        Yii::$app->params['jplag'] = [
            'jre' => 'java',
            'jar' => '/dev/null',
            'report-viewer' => 'https://jplag.github.io/JPlag/',
        ];
    }

    public function _after()
    {
        unset(Yii::$app->params['jplag']);
        $this->tester->deleteDir(Yii::getAlias("@appdata"));
    }

    public function _fixtures()
    {
        return [
            'plagiarisms' => [
                'class' => PlagiarismFixture::class,
            ],
            'plagiarisms_jplag' => [
                'class' => JPlagPlagiarismFixture::class,
            ],
            'studentfiles' => [
                'class' => StudentFilesFixture::class,
            ],
        ];
    }

    private function getFinder(string $fixtureIndex): TestableJPlagPlagiarismFinder
    {
        $plagiarism = $this->tester->grabFixture('plagiarisms', $fixtureIndex);
        return new TestableJPlagPlagiarismFinder($plagiarism);
    }

    public function testBasic()
    {
        $finder = $this->getFinder('plagiarism9');
        $finder->start();
        $this->assertEquals(
            "java -jar /dev/null  -new '{$this->appdata_dir}/plagiarism/9/5009/' -l 'cpp' -bc '{$this->appdata_dir}/plagiarism/9/basefiles' -t '1' -r '{$this->appdata_dir}/plagiarism/plagiarism-result/9/result'",
            $finder->command
        );
    }
}
