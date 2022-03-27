<?php

namespace app\components\docker;

use Docker\API\Exception\ContainerDeleteNotFoundException;
use Docker\API\Model\ContainersCreatePostBody;
use Docker\API\Model\ContainersCreatePostResponse201;
use Docker\API\Model\ContainersIdExecPostBody;
use Docker\API\Model\ContainersIdJsonGetResponse200;
use Docker\API\Model\ExecIdStartPostBody;
use Docker\Docker;
use ForceUTF8\Encoding;
use Psr\Http\Message\ResponseInterface;
use Yii;

/**
 * Represents a Docker container instance.
 */
class DockerContainer
{
    /**
     * Creates a DockerContainer for an already existing and running container by name
     * @param string $os
     * @param string $existingContainerName
     * @return DockerContainer|null null if the container not exists
     * @throws \yii\base\InvalidConfigException
     */
    public static function createForRunning(string $os, string $existingContainerName): ?DockerContainer
    {
        $dockerContainer = new DockerContainer($os);
        $dockerContainer->containerName = $existingContainerName;
        if (empty($dockerContainer->getContainerInspectResult())) {
            Yii::error("Container with name [$existingContainerName] for OS [$os] does not exists", __METHOD__);
            return null;
        }
        return $dockerContainer;
    }

    private Docker $docker;
    private string $containerName;
    /**
     * @var ContainersCreatePostResponse201|ResponseInterface|null
     */
    private $containerCreateResult;
    /**
     * @var ContainersIdJsonGetResponse200|ResponseInterface|null
     */
    private $containerInspectResult;

    /**
     * Initializes a Docker Client to the specified OS instance.
     *
     * @param string $os
     * @throws \yii\base\InvalidConfigException if the Docker client to the OS not configured.
     */
    public function __construct(string $os)
    {
        $this->docker = Yii::$container->get(Docker::class, ['os' => $os]);
    }

    /**
     * Creates a container based on the configuration.
     * <p>
     * <b>This is a one time operation: if the container already created the call will be ignored!</b>
     *
     * @param ContainersCreatePostBody $containerConfig
     * @param string $containerName
     * @return void
     */
    public function createContainer(ContainersCreatePostBody $containerConfig, string $containerName)
    {
        //TODO: check is image already built, is it really needed?
        if (!$this->isContainerCreated()) {
            try {
                $this->containerName = $containerName;
                $this->containerCreateResult = $this->docker->containerCreate(
                    $containerConfig,
                    ['name' => $containerName]
                );
            } catch (\Exception $e) {
                Yii::info("Container [$containerName] is already running, shutting down and retry start", __METHOD__);
                $this->docker->containerStop($containerName);
                $this->docker->containerDelete($containerName);
                $this->containerCreateResult = $this->docker->containerCreate(
                    $containerConfig,
                    ['name' => $containerName]
                );
            } finally {
                if (!is_null($this->containerCreateResult) && !empty($this->containerCreateResult->getWarnings())) {
                    Yii::info(
                        "Container [$containerName] is started with warnings: "
                        . implode(", ", $this->containerCreateResult->getWarnings()),
                        __METHOD__
                    );
                }
            }
        }
    }

    /**
     * Starts the configured container instance. If the configuration not yet set the call will be ignored.
     * <p>
     * <b>This is a one time operation: if the container already started the call will be ignored!</b>
     *
     * @return void
     * @see DockerContainer::createContainer
     */
    public function startContainer()
    {
        if ($this->isContainerCreated() && !$this->isContainerRunning()) {
            $this->docker->containerStart($this->containerCreateResult->getId());
            $this->containerInspectResult =
                $this->docker->containerInspect($this->containerCreateResult->getId());
        }
    }

    /**
     * Executes a command in a running container.
     * <p>
     * <b>If the container not yet started to call will be ignored!</b>
     *
     * @param array $commandDetails an array containing the command and it's parameters. For example ['g++', 'main.cpp']
     * @return array|null containing the <code>stdout</code>, <code>stderr</code> logs and the <code>exitCode</code>.
     * Or null if the container is not running
     */
    public function executeCommand(array $commandDetails): ?array
    {
        if (!$this->isContainerRunning()) {
            return null;
        }
        $execConfig = new ContainersIdExecPostBody();
        $execConfig->setAttachStdout(true);
        $execConfig->setAttachStderr(true);
        $execConfig->setCmd($commandDetails);

        $execCreateResult = $this->docker->containerExec($this->containerCreateResult->getId(), $execConfig);

        $execStartConfig = new ExecIdStartPostBody();
        $execStartConfig->setDetach(false);
        $execStartConfig->setTty(false);

        /** @var \Docker\Stream\DockerRawStream $stream */
        $stream = $this->docker->execStart(
            $execCreateResult->getId(),
            $execStartConfig
        );

        $stdoutFull = "";
        $stderrFull = "";
        $stream->onStdout(function ($stdout) use (&$stdoutFull) {
            $stdoutFull .= $stdout;
        });
        $stream->onStderr(function ($stderr) use (&$stderrFull) {
            $stderrFull .= $stderr;
        });
        $stream->wait();

        $execFindResult = $this->docker->execInspect($execCreateResult->getId());
        $exitCode = $execFindResult->getExitCode();

        return [
            'stdout' => Encoding::toUTF8($stdoutFull),
            'stderr' => Encoding::toUTF8($stderrFull),
            'exitCode' => $exitCode
        ];
    }

    /**
     * Uploads a tar file to the container to the specified path in the container.
     * <p>
     * <b>If the container not yet created to call will be ignored!</b>
     * <p>
     * <b>It's the caller responsibility to pass valid target filesystem path syntax!</b>
     *
     * @param string $sourceTarPath
     * @param string $targetPath
     * @return void
     */
    public function uploadArchive(
        string $sourceTarPath,
        string $targetPath
    ) {
        if ($this->isContainerCreated()) {
            $this->docker->putContainerArchive(
                $this->containerCreateResult->getId(),
                file_get_contents($sourceTarPath),
                [
                    'path' => $targetPath
                ]
            );
        }
    }

    /**
     * Shuts down and deletes the container. If the container not yet created the call will be ignored.
     * @return void
     * @see DockerContainer::createContainer()
     * @see DockerContainer::startContainer()
     */
    public function stopContainer()
    {
        if ($this->isContainerRunning()) {
            $this->docker->containerKill($this->containerInspectResult->getId());
            try {
                $this->docker->containerDelete($this->containerInspectResult->getId());
            } catch (ContainerDeleteNotFoundException $e) {
                Yii::info("Container [$this->containerName] already deleted", __METHOD__);
            }
        } else if ($this->isContainerCreated()) {
            try {
                $this->docker->containerDelete($this->containerCreateResult->getId());
            } catch (ContainerDeleteNotFoundException $e) {
                Yii::info("Container [$this->containerName] already deleted", __METHOD__);
            }
        }
        $this->containerInspectResult = null;
        $this->containerCreateResult = null;
    }

    /**
     * @return string
     */
    public function getContainerName(): string
    {
        return $this->containerName;
    }

    /**
     * @return ContainersIdJsonGetResponse200|ResponseInterface|null
     */
    public function getContainerInspectResult()
    {
        if (empty($this->containerInspectResult) && empty($this->containerCreateResult)) {
            try {
                $inspect = $this->docker->containerInspect($this->containerName);
                $this->containerInspectResult = $inspect;
            } catch (\Exception $ignored) {
                Yii::debug("Container inspect failed for container [$this->containerName]", __METHOD__);
                //only to fetch inspect on pre-existing container
            }
        }
        return $this->containerInspectResult;
    }

    /**
     * @return ContainersCreatePostResponse201|ResponseInterface|null
     */
    public function getContainerCreateResult()
    {
        return $this->containerCreateResult;
    }

    private function isContainerCreated(): bool
    {
        return ($this->containerCreateResult instanceof ContainersCreatePostResponse201)
        || (
            $this->containerCreateResult instanceof ResponseInterface
            && $this->containerCreateResult->getStatusCode() == 201
            );
    }

    private function isContainerRunning(): bool
    {
        return ($this->containerInspectResult instanceof ContainersIdJsonGetResponse200)
        || (
            $this->containerInspectResult instanceof ResponseInterface
            && $this->containerInspectResult->getStatusCode() == 200
            );
    }
}
