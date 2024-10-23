<?php

namespace app\tests\unit;

use app\components\codechecker\SubmissionToAnalyzeFinder;
use app\models\Task;
use app\tests\unit\fixtures\SubmissionsFixture;
use app\tests\unit\fixtures\TaskFixture;
use Codeception\Test\Unit;

class SubmissionToAnalyzeFinderTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'task' => [
                'class' => TaskFixture::class
            ],
            'submission' => [
                'class' => SubmissionsFixture::class,
            ],
        ];
    }

    public function testFileFound()
    {
        $finder = new SubmissionToAnalyzeFinder();
        $file = $finder->findNext();

        $this->assertNotNull($file);
        $this->assertEquals(2, $file->id);
    }

    public function testFileNotFound()
    {
        Task::updateAll(['staticCodeAnalysis' => 0]);
        $finder = new SubmissionToAnalyzeFinder();
        $file = $finder->findNext();

        $this->assertNull($file);
    }
}
