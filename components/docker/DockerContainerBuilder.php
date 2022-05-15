<?php

namespace app\components\docker;

use app\models\Task;
use Docker\API\Model\ContainersCreatePostBody;
use Docker\API\Model\HostConfig;
use Docker\API\Model\PortBinding;
use Yii;

/**
 * Fluent style builder for Docker containers
 */
class DockerContainerBuilder
{
    /**
     * Initializes a builder based on a task's properties.
     * The following container attributes derived from task:
     * <ul>
     *  <li> OS type
     *  <li> image name
     *  <li> port to expose (in case of web app)
     * </ul>
     *
     * Default working dir:
     * <ul>
     *  <li> For Windows: <i>C:\\test\\submission</i>
     *  <li> For Linux: <i>/test/submission</i>
     * </ul>
     *
     * Default command:
     * <ul>
     *  <li> For Windows: <i>powershell</i>
     *  <li> For Linux: <i>/bin/bash</i>
     * </ul>
     *
     * @param Task $task the imaged will be based on
     * @param bool $setDefaultWorkingDir whether to set default working dir
     * @param bool $setDefaultCommand whether to set default command
     * @return DockerContainerBuilder
     */
    public static function forTask(
        Task $task,
        bool $setDefaultWorkingDir = true,
        bool $setDefaultCommand = true
    ): DockerContainerBuilder {
        if ($task->appType == Task::APP_TYPE_WEB) {
            $builder = new DockerContainerBuilder($task->testOS, $task->imageName, $task->port);
        } else {
            $builder = new DockerContainerBuilder($task->testOS, $task->imageName);
        }
        if ($setDefaultWorkingDir) {
            if ($task->testOS == 'windows') {
                $builder->withWorkingDir('C:\\test\\submission');
            } else {
                $builder->withWorkingDir('/test/submission');
            }
        }
        if ($setDefaultCommand) {
            if ($task->testOS == 'windows') {
                $builder->withCommand(['powershell']);
            } else {
                $builder->withCommand(['/bin/bash']);
            }
        }
        return $builder;
    }

    private string $os;
    private string $imageName;
    private ?array $command;
    private ?string $workingDir;
    private bool $withTty = true;
    private bool $networkApp = false;
    private array $portMappings = [];

    /**
     * Creates a container builder.
     * @param string $os the image OS type.
     * @param string $imageName The name of the image to use when creating the container, or which was used when the container was created.
     * @param int|null $webAppPort The initial port to expose on the container on start if any.
     */
    public function __construct(string $os, string $imageName, int $webAppPort = null)
    {
        $this->os = $os;
        $this->imageName = $imageName;
        if (!empty($webAppPort)) {
            $this->networkApp = true;
            $this->portMappings[$webAppPort] = '';
        }
    }

    /**
     * Whether to allocate pseudo-TTY for the container.
     * Be default thr TTY is allocated.
     *
     * @param bool $withTty
     * @return $this the builder
     */
    public function withTty(bool $withTty): DockerContainerBuilder
    {
        $this->withTty = $withTty;
        return $this;
    }

    /**
     * Sets the working dir of the container to be built.
     * <p>
     * <b>It's the caller responsibility to pass valid filesystem path syntax!</b>
     *
     * @param string $workingDir
     * @return $this the builder
     */
    public function withWorkingDir(string $workingDir): DockerContainerBuilder
    {
        $this->workingDir = $workingDir;
        return $this;
    }

    /**
     * Sets the command (CMD) to be executed on container start.
     *
     * @param array $command
     * @return $this the builder
     */
    public function withCommand(array $command): DockerContainerBuilder
    {
        $this->command = $command;
        return $this;
    }

    /**
     * <b>Only if the $webAppPort was set on the constructor.</b>
     *
     * Binds the $webAppPort to the host port
     *
     * @param int $hostPort
     * @return DockerContainerBuilder
     */
    public function withHostPort(int $hostPort)
    {
        if ($this->networkApp) {
            $webAppPort = array_keys($this->portMappings)[0];
            $this->portMappings[$webAppPort] = $hostPort;
        }
        return $this;
    }

    /**
     * Creates the configured container. The returned container is not yet started.
     *
     * @param string|null $containerName if not set a random string will be generated with prefix: <i>tms_</i>
     * @return DockerContainer
     * @throws \yii\base\Exception if the container creation or start fails.
     */
    public function build(?string $containerName = null): DockerContainer
    {
        $config = $this->createDefaultConfig();
        $config = $this->appendNetworkConfig($config);
        $config = $this->appendSecurityDefaults($config);

        $dockerContainer = new DockerContainer($this->os);
        $dockerContainer->createContainer(
            $config,
            $containerName == null ? $this->generateRandomName() : $containerName
        );

        return $dockerContainer;
    }

    private function createDefaultConfig(): ContainersCreatePostBody
    {
        $config = new ContainersCreatePostBody();
        $config->setImage($this->imageName);
        $config->setTty($this->withTty);

        if (!empty($this->workingDir)) {
            $config->setWorkingDir($this->workingDir);
        }

        if (!empty($this->command)) {
            $config->setCmd($this->command);
        }
        return $config;
    }

    private function appendNetworkConfig(ContainersCreatePostBody $config): ContainersCreatePostBody
    {
        if (!$this->networkApp) {
            return $config;
        }

        $exposedPorts = [];
        foreach (array_keys($this->portMappings) as $portToExpose) {
            $exposedPorts[$portToExpose . '/tcp'] = new \stdClass();
        }
        $config->setExposedPorts(new \ArrayObject($exposedPorts));

        if (empty($config->getHostConfig())) {
            $hostConfig = new HostConfig();
            $config->setHostConfig($hostConfig);
        } else {
            $hostConfig = $config->getHostConfig();
        }

        $portBindings = [];
        foreach ($this->portMappings as $dockerPort => $hostPort) {
            if (!empty($hostPort)) {
                $portBinding = new PortBinding();
                $portBinding->setHostPort($hostPort);
                $portBindings[$dockerPort . '/tcp'] = [$portBinding];
            }
        }
        $hostConfig->setPortBindings(new \ArrayObject($portBindings));

        return $config;
    }

    /**
     * @throws \yii\base\Exception
     */
    private function generateRandomName(): string
    {
        return 'tms_' . Yii::$app->security->generateRandomString(12);
    }

    private function appendSecurityDefaults(ContainersCreatePostBody $config): ContainersCreatePostBody
    {
        /* TODO add sensible security config:
         *  * resource consumption limits
         *  * disable network access
         *  * timeouts
         */

        return $config;
    }
}
