<?php

namespace app\tests\unit;

use app\models\Task;
use DateInterval;
use DateTime;

class TaskTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'tasks' => [
                'class' => \app\tests\unit\fixtures\TaskFixture::class
            ],
            'subscriptions' => [
                'class' => \app\tests\unit\fixtures\SubscriptionFixture::class
            ]
        ];
    }

    // tests
    public function testTaskWithStudents()
    {
        $tasks = Task::find()->withStudents(true)->all();
        $notAccepted = [];
        foreach ($tasks as $task) {
            $notAccepted = array_merge(
                $notAccepted,
                array_filter($task->group->subscriptions, fn($sub) => $sub->isAccepted == 0)
            );
        }
        $this->tester->assertIsEmpty(
            $notAccepted,
            "Only students with accepted subscriptions should be fetched"
        );
    }

    public function testTaskWithOncomingDeadline()
    {
        $now = new DateTime();
        $deadline = new DateTime();
        $deadline->add(new DateInterval('P3D'))->setTime(23, 59, 59);
        $tasks = Task::find()->oncomingDeadline(3)->all();
        $notAvailable = [];
        $beyondDeadline = [];

        foreach ($tasks as $task) {
            if ($task->available != null && new DateTime($task->available) > $now) {
                print_r($tasks);
                $notAvailable[] = $task;
            }
            if (new DateTime($task->hardDeadline) > $deadline) {
                $beyondDeadline[] = $task;
            }
        }

        $this->tester->assertIsEmpty(
            $notAvailable,
            "Only available tasks should be fetched"
        );
        $this->tester->assertIsEmpty(
            $beyondDeadline,
            "Only tasks with deadline before: " . $deadline->format('Y-m-d H:i:s') . " should be fetched"
        );
    }

    public function testCanvasURLIsNotCanvasGroup()
    {
        $task = Task::findOne(5000);
        $this->assertNull($task->canvasUrl);
    }

    public function testCanvasURLIsCanvasGroup()
    {
        $task = Task::findOne(5006);
        $this->assertEquals('https://canvas.example.com/courses/1/assignments/2', $task->canvasUrl);
    }
}
