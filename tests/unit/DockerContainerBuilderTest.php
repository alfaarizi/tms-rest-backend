<?php

namespace app\tests\unit;

use app\components\docker\DockerContainerBuilder;
use app\components\docker\DockerImageManager;
use app\models\Task;
use app\tests\doubles\DockerStub;
use Docker\Docker;
use Yii;

class DockerContainerBuilderTest extends \Codeception\Test\Unit
{
    use \Codeception\Specify;

    /** @specify  */
    private Task $task;

    private DockerStub $dockerStub;

    protected function _before()
    {
        $dockerImageManagerMock = $this->createMock(DockerImageManager::class);
        $dockerImageManagerMock->method('alreadyBuilt')->willReturn(true);
        Yii::$container->set(DockerImageManager::class, $dockerImageManagerMock);

        $task = new Task();
        $task->testOS = 'linux';
        $task->appType = 'Web';
        $task->imageName = 'httpd:alpine';
        $task->port = 80;
        $this->task = $task;
    }

    // tests
    public function testForTask()
    {
        Yii::$container->set(Docker::class, function ($container, $params, $config) {
            $this->dockerStub = new DockerStub($params['os']);
            return $this->dockerStub;
        });

        $this->specify('Create linux container with defaults', function () {
            $builder = DockerContainerBuilder::forTask($this->task)->build();

            $config = $this->dockerStub->createPostBody;

            self::assertEquals(['/bin/bash'], $config->getCmd());
            self::assertEquals('httpd:alpine', $config->getImage());
            self::assertEquals('/test/submission', $config->getWorkingDir());
            self::assertStringStartsWith('tms_', $this->dockerStub->createQueryParams['name']);
            self::assertEquals('80/tcp', $this->getExposedPort($config));
        });

        $this->specify('Create windows container with defaults', function () {
            $this->task->testOS = 'windows';
            $builder = DockerContainerBuilder::forTask($this->task)->build();

            $config = $this->dockerStub->createPostBody;

            self::assertEquals(['powershell'], $config->getCmd());
            self::assertEquals('httpd:alpine', $config->getImage());
            self::assertEquals('C:\\test\\submission', $config->getWorkingDir());
            self::assertEquals('80/tcp', $this->getExposedPort($config));
            self::assertStringStartsWith('tms_', $this->dockerStub->createQueryParams['name']);
        });

        $this->specify('Create with overrides', function () {
            $builder = DockerContainerBuilder::forTask($this->task)
                ->withHostPort(8009)
                ->withWorkingDir('myDir')
                ->withCommand(['myCmd'])
                ->withTty(false)
                ->withEnv('FOO', 'BAR')
                ->withNetworkMode("container:foo");
            $builder->build('myName');

            $config = $this->dockerStub->createPostBody;

            self::assertEquals(['myCmd'], $config->getCmd());
            self::assertEquals('httpd:alpine', $config->getImage());
            self::assertEquals('myDir', $config->getWorkingDir());
            self::assertStringStartsWith('myName', $this->dockerStub->createQueryParams['name']);
            self::assertEquals('80/tcp', $this->getExposedPort($config));
            self::assertEquals('FOO=BAR', $config->getEnv()[0]);
            self::assertEquals('container:foo', $config->getHostConfig()->getNetworkMode());

            $portBindings = $this->getPortBindings($config);
            $exposedPort = array_key_first($portBindings);
            self::assertEquals('80/tcp', $exposedPort);
            self::assertEquals('8009', $portBindings[$exposedPort][0]->getHostPort());
        });
    }

    private function getExposedPort(\Docker\API\Model\ContainersCreatePostBody $config)
    {
        $exposedPorts = $config->getExposedPorts()->getArrayCopy();
        return array_key_first($exposedPorts);
    }

    private function getPortBindings(\Docker\API\Model\ContainersCreatePostBody $config)
    {
        return $config->getHostConfig()->getPortBindings()->getArrayCopy();
    }
}
