<?php

namespace app\tests\unit;

use app\components\codechecker\StudentFileToAnalyzeFinder;
use app\models\Task;
use app\tests\unit\fixtures\StudentFilesFixture;
use app\tests\unit\fixtures\TaskFixture;
use Codeception\Test\Unit;

class StudentFileToAnalyzeFinderTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'task' => [
                'class' => TaskFixture::class
            ],
            'studentfiles' => [
                'class' => StudentFilesFixture::class,
            ],
        ];
    }

    public function testFileFound()
    {
        $finder = new StudentFileToAnalyzeFinder();
        $file = $finder->findNext();

        $this->assertNotNull($file);
        $this->assertEquals(2, $file->id);
    }

    public function testFileNotFound()
    {
        Task::updateAll(['staticCodeAnalysis' => 0]);
        $finder = new StudentFileToAnalyzeFinder();
        $file = $finder->findNext();

        $this->assertNull($file);
    }
}
