<?php

namespace app\tests\unit;

use app\models\Task;
use DateInterval;
use DateTime;

class TaskTest extends \Codeception\Test\Unit
{
    use \Codeception\Specify;

    /**
     * @var \UnitTester
     */
    protected $tester;

    /** @specify  */
    private Task $task;

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

    public function testValidation()
    {
        $this->task = new Task();
        $this->task->createrID = 1;
        $this->task->imageName = 'foo';
        $this->task->appType = 'Web';
        $this->task->port = 8080;

        $this->specify("App type must be in [Web, Console] when image set", function () {
            $this->assertTrue(
                $this->task->validate('appType'),
                'Web type should be allowed'
            );

            $this->task->appType = 'Console';
            $this->assertTrue(
                $this->task->validate('appType'),
                'Console type should be allowed'
            );

            $this->task->appType = null;
            $this->assertFalse(
                $this->task->validate('appType'),
                'Port must be set'
            );

            $this->task->imageName = null;
            $this->assertTrue(
                $this->task->validate('appType'),
                'Null should be allowed'
            );
        });

        $this->specify("Port must be set when app type is Web", function () {
            $this->assertTrue($this->task->validate('port'), 'port must be set');
            $this->task->port = null;
            $this->assertFalse($this->task->validate('port'), 'null port shouldn\'t be allowed');
            $this->task->appType = 'Console';
            $this->assertTrue($this->task->validate('port'), 'null port should be allowed');
        });

        $this->specify("Port must be in valid range", function () {
            $this->assertTrue($this->task->validate('port'), 'port must be set');
            $this->task->port = -1;
            $this->assertFalse($this->task->validate('port'), 'port must be >= 0');
            $this->task->port = 99999;
            $this->assertFalse($this->task->validate('port'), 'port must be <= 65353');
        });

        $this->specify("Static code analyzer tool should be supported", function () {
            $this->task->staticCodeAnalysis = true;

            $this->task->staticCodeAnalyzerTool = "codechecker";
            $this->assertTrue($this->task->validate("staticCodeAnalyzerTool"));

            $this->task->staticCodeAnalyzerTool = "roslynator";
            $this->assertTrue($this->task->validate("staticCodeAnalyzerTool"));

            $this->task->staticCodeAnalyzerTool = "unknown";
            $this->assertFalse($this->task->validate("staticCodeAnalyzerTool"));
        });

        $this->specify("Static code analyzer: 'codeCheckerCompileInstructions' is required when CodeChecker selected", function () {
            $this->task->staticCodeAnalysis = true;
            $this->task->staticCodeAnalyzerTool = "codechecker";

            $this->task->codeCheckerCompileInstructions = "g++ *cpp";
            $this->assertTrue($this->task->validate("codeCheckerCompileInstructions"));

            $this->task->codeCheckerCompileInstructions = null;
            $this->assertFalse($this->task->validate("codeCheckerCompileInstructions"));
        });

        $this->specify("Static code analyzer: 'staticCodeAnalyzerInstructions' is required when other analyzer is selected", function () {
            $this->task->staticCodeAnalysis = true;
            $this->task->staticCodeAnalyzerTool = "roslynator";

            $this->task->staticCodeAnalyzerInstructions = "roslynator analyze";
            $this->assertTrue($this->task->validate("staticCodeAnalyzerInstructions"));

            $this->task->staticCodeAnalyzerInstructions = null;
            $this->assertFalse($this->task->validate("staticCodeAnalyzerInstructions"));
        });
    }
}
