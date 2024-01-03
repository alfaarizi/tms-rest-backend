<?php

namespace app\tests\unit;

use app\components\docker\DockerImageManager;
use app\components\SubmissionRunner;
use app\models\StudentFile;
use app\tests\unit\fixtures\StudentFilesFixture;
use Yii;
use yii\helpers\FileHelper;

class SubmissionRunnerTest extends \Codeception\Test\Unit
{
    use \Codeception\Specify;

    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @specify
     */
    private StudentFile $studentfile;

    private SubmissionRunner $submissionRunner;

    public function _fixtures()
    {
        return [
            'studentFiles' => [
                'class' => StudentFilesFixture::class
            ]
        ];
    }

    protected function _before()
    {
        $dockerImageManagerMock = $this->createMock(DockerImageManager::class);
        $dockerImageManagerMock->method('alreadyBuilt')->willReturn(true);
        Yii::$container->set(DockerImageManager::class, $dockerImageManagerMock);

        $this->submissionRunner = new SubmissionRunner();
        $this->studentfile = $this->tester->grabRecord(StudentFile::class, ['id' => 5]);

        $from = Yii::$app->basePath . '/tests/_data/appdata_samples/uploadedfiles/5007/stud02/stud02.zip';

        FileHelper::createDirectory(Yii::getAlias("@appdata/uploadedfiles/5007/stud02/"), 0777, true);
        $to = Yii::getAlias("@appdata/uploadedfiles/5007/stud02/stud02.zip");
        copy($from, $to);
    }

    protected function _after()
    {
        FileHelper::removeDirectory(Yii::getAlias("@appdata/uploadedfiles/5007/"));
        FileHelper::removeDirectory(Yii::getAlias("@tmp"));
    }

    // tests
    public function testRun()
    {
        $this->specify("When all conditions met container should start", function () {
            $this->studentfile->task->appType = 'Web';
            $this->studentfile->task->imageName = 'busybox';
            $this->studentfile->task->port = 8080;
            $this->studentfile->task->compileInstructions = 'echo hi';

            $container = $this->submissionRunner
                ->run($this->studentfile, 8009, $this->studentfile->containerName);
            self::assertEquals(
                2,
                count(scandir(Yii::getAlias("@tmp/docker"))),
                'Tmp dir should be empty after container start'
            );
            self::assertEquals($this->studentfile->containerName, $container->getContainerName());
        });

        $this->specify("When container fails to start resources should be cleaned up", function () {
            $this->studentfile->task->appType = 'Web';
            $this->studentfile->task->port = 8080;

            $this->tester->expectThrowable(\Throwable::class, function () {
                $this->submissionRunner
                    ->run($this->studentfile, 8009, $this->studentfile->containerName);
            });
            self::assertEquals(
                2,
                count(scandir(Yii::getAlias("@tmp/docker"))),
                'Tmp dir should be empty after container start'
            );
        });
    }
}
