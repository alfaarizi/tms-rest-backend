<?php

namespace app\tests\unit;

use app\models\Task;
use app\tests\unit\fixtures\TaskFixture;

class TaskTest extends \Codeception\Test\Unit
{
    public function _fixtures()
    {
        return [
            'tasks' => [
                'class' => TaskFixture::class
            ],
        ];
    }

    public function testCanvasURLIsNotCanvasGroup()
    {
        $task = Task::findOne(5000);
        $this->assertNull($task->canvasUrl);
    }

    public function testCanvasURLIsCanvasGroup()
    {
        $task = Task::findOne(5006);
        $this->assertEquals('https://canvas.example.com//courses/1/assignments/2', $task->canvasUrl);
    }
}
