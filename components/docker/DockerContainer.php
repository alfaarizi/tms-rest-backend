<?php

namespace app\components\docker;

use app\exceptions\DockerContainerException;
use Docker\API\Exception\ContainerDeleteNotFoundException;
use Docker\API\Model\ContainersCreatePostBody;
use Docker\API\Model\ContainersCreatePostResponse201;
use Docker\API\Model\ContainersIdExecPostBody;
use Docker\API\Model\ContainersIdJsonGetResponse200;
use Docker\API\Model\ExecIdStartPostBody;
use Docker\API\Model\SystemInfo;
use Docker\Docker;
use ForceUTF8\Encoding;
use Jane\OpenApiRuntime\Client\Client;
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
    private DockerImageManager $dockerImageManager;
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
     * Information about the Docker instance
     * @var SystemInfo
     */
    private SystemInfo $systemInfo;

    /**
     * Initializes a Docker Client to the specified OS instance.
     *
     * @param string $os
     * @throws \yii\base\InvalidConfigException if the Docker client to the OS not configured.
     */
    public function __construct(string $os)
    {
        $this->dockerImageManager = Yii::$container->get(DockerImageManager::class, ['os' => $os]);
        $this->docker = Yii::$container->get(Docker::class, ['os' => $os]);
        $this->systemInfo = $this->docker->systemInfo();
    }

    /**
     * Creates a container based on the configuration.
     * <p>
     * <b>This is a one time operation: if the container already created the call will be ignored!</b>
     *
     * @param ContainersCreatePostBody $containerConfig
     * @param string $containerName
     * @return void
     * @throws DockerContainerException
     */
    public function createContainer(ContainersCreatePostBody $containerConfig, string $containerName)
    {
        if (!$this->dockerImageManager->alreadyBuilt($containerConfig->getImage())) {
            if (str_starts_with($containerConfig->getImage(), 'tms/')) {
                throw new DockerContainerException(Yii::t(
                    "app",
                    "The requested local Docker image ({imageName}) is not available!",
                    ["imageName" => $containerConfig->getImage()]
                ));
            }
            $this->dockerImageManager->pullImage($containerConfig->getImage());
        }

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
            try {
                $this->docker->containerStart($this->containerCreateResult->getId());
            } catch (\Exception $e) {
                // TODO: implement better logic for waiting on Docker containers to start on Windows with hyperv isolation
            }
            $this->inspectContainer($this->containerCreateResult->getId());
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
    public function executeCommand(array $commandDetails, bool $attachedMode = true): ?array
    {
        if (!$this->isContainerRunning()) {
            return null;
        }
        $execConfig = new ContainersIdExecPostBody();
        $execConfig->setAttachStdout($attachedMode);
        $execConfig->setAttachStderr($attachedMode);
        $execConfig->setCmd($commandDetails);

        $execCreateResult = $this->docker->containerExec($this->containerCreateResult->getId(), $execConfig);

        $execStartConfig = new ExecIdStartPostBody();
        $execStartConfig->setDetach(!$attachedMode);
        $execStartConfig->setTty(false);

        /** @var \Docker\Stream\DockerRawStream $stream */
        $stream = $this->docker->execStart(
            $execCreateResult->getId(),
            $execStartConfig
        );

        $stdoutFull = "";
        $stderrFull = "";
        $exitCode = 0;
        if ($attachedMode) {
            $stream->onStdout(function ($stdout) use (&$stdoutFull) {
                if (mb_strlen($stdoutFull) + mb_strlen($stdout) < 1024 * 1024) { // 1 MB should be enough
                    $stdoutFull .= $stdout;
                } else {
                    throw new \OverflowException(Yii::t('app', 'Your solution exceeded the maximum output size.'));
                }
            });
            $stream->onStderr(function ($stderr) use (&$stderrFull) {
                if (mb_strlen($stderrFull) + mb_strlen($stderr) < 65000) { // Submission::errorMsg field is 65535 in size
                    $stderrFull .= $stderr;
                } else {
                    throw new \OverflowException(
                        Yii::t('app', 'Your solution exceeded the maximum error output size.')
                    );
                }
            });

            try {
                $stream->wait();
                $execFindResult = $this->docker->execInspect($execCreateResult->getId());
                $exitCode = $execFindResult->getExitCode();
            } catch (\OverflowException $ex) {
                $stderrFull .= $ex->getMessage() . PHP_EOL;
                $exitCode = -1;
            }
        }

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
            // The container must be stopped before uploading files when a Windows host with Hyper-V isolation is configured
            $shouldStop = $this->systemInfo->getOSType() == 'windows' && $this->systemInfo->getIsolation() == 'hyperv';
            if ($shouldStop) {
                $this->docker->containerStop($this->containerCreateResult->getId());
            }

            $this->docker->putContainerArchive(
                $this->containerCreateResult->getId(),
                file_get_contents($sourceTarPath),
                [
                    'path' => $targetPath
                ]
            );

            if ($shouldStop) {
                try {
                    $this->docker->containerStart($this->containerCreateResult->getId());
                } catch (\Exception $e) {
                    // TODO: implement better logic for waiting on Docker containers to start on Windows with hyperv isolation
                }
                $this->containerInspectResult = $this->docker->containerInspect($this->containerCreateResult->getId());
            }
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
            $this->docker->containerKill($this->containerInspectResult['Id']);
            try {
                $this->docker->containerDelete($this->containerInspectResult['Id']);
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
                $this->inspectContainer($this->containerName);
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
        return !empty($this->containerInspectResult);
    }

    /**
     * Workaround of https://github.com/docker-php/docker-php/issues/348
     * @param string $containerId
     */
    private function inspectContainer(string $containerId)
    {
        //fetch response workaround: https://github.com/docker-php/docker-php/issues/348
        $containerInspect = $this->docker->containerInspect(
            $this->containerName,
            [],
            Client::FETCH_RESPONSE
        );

        $contents = $containerInspect->getBody()->getContents();
        $this->containerInspectResult = json_decode($contents, true);
    }

    /**
     * Extracts resources from container into a tar archive
     *
     * @param string $pathToResource path to resources to extract from the container
     * @param string $destination path to tar file on host
     * @return void
     */
    public function downloadArchive(string $pathToResource, string $destination)
    {
        $id = $this->getContainerInspectResult()['Id'];
        // The container must be stopped before uploading files when a Windows host with Hyper-V isolation is configured
        $shouldStop = $this->systemInfo->getOSType() == 'windows' && $this->systemInfo->getIsolation() == 'hyperv';
        if ($shouldStop) {
            $this->docker->containerStop($id);
        }

        $containerArchive = $this->docker->containerArchive(
            $id,
            ['path' => $pathToResource],
            Client::FETCH_RESPONSE
        );
        file_put_contents($destination, $containerArchive->getBody()->getContents(), LOCK_EX);

        if ($shouldStop) {
            try {
                $this->docker->containerStart($id);
            } catch (\Exception $e) {
                // TODO: implement better logic for waiting on Docker containers to start on Windows with hyperv isolation
            }
            $this->containerInspectResult = $this->docker->containerInspect($id);
        }
    }
}
