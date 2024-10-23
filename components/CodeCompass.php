<?php

namespace app\components;

use app\models\Submission;
use ArrayObject;
use Docker\API\Exception\ContainerDeleteBadRequestException;
use Docker\API\Exception\ContainerDeleteConflictException;
use Docker\API\Exception\ContainerDeleteInternalServerErrorException;
use Docker\API\Exception\ContainerDeleteNotFoundException;
use Docker\API\Model\ContainersCreatePostBody;
use Docker\API\Model\ContainersIdExecPostBody;
use Docker\API\Model\ContainersIdJsonGetResponse200;
use Docker\API\Model\ExecIdStartPostBody;
use Docker\API\Model\HostConfig;
use Docker\API\Model\IdResponse;
use Docker\API\Model\PortBinding;
use Docker\Docker;
use Docker\Stream\CallbackStream;
use Docker\Stream\DockerRawStream;
use PharData;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use Yii;
use yii\base\BaseObject;
use yii\base\ErrorException;
use yii\helpers\FileHelper;
 use ZipArchive;

/**
 * The class that is responsible for starting and stopping CodeCompass webservers
 * in docker containers.
 *
 * @property-read string $containerId
 * @property-read string $errorLogs
 * @property-read string $codeCompassUsername
 * @property-read string $codeCompassPassword
 */
class CodeCompass extends BaseObject
{
    private const DATABASE_DIRECTORY = '/datafiles';
    private const WORK_DIRECTORY = '/parsed';
    private const PROJECT_DIRECTORY = '/workspace';
    private const COMPILE_COMMANDS_DIRECTORY = '/compile_commands';
    private const SQLITE_DATA_FILE = 'sqlite:database=' . self::DATABASE_DIRECTORY . '/data.sqlite';
    private const BUILD_SCRIPT_FILE = 'build.sh';
    private const INSTALL_SCRIPT_FILE = 'install.sh';
    private const COMPILATION_COMMANDS_FILE_LOCATION = self::COMPILE_COMMANDS_DIRECTORY . '/compilation_commands.json';

    private Submission $_submission;
    private Docker $_docker;
    private string $_port;
    private string $_projectBasePath;
    private string $_errorLogs = '';

    private ?string $_imageName;
    private string $_containerId;

    private string $_codeCompassPassword = '';
    private string $_codeCompassUsername = '';

    public function __construct(Submission $submission, Docker $docker, string $port, ?string $imageName = null)
    {
        $this->_submission = $submission;
        $this->_port = $port;
        $this->_imageName = $imageName;
        $this->_docker = $docker;

        $this->_containerId = 'compass_' . $this->_submission->id;
        $this->_projectBasePath = Yii::getAlias("@tmp/codecompass/") . $this->_submission->id;
        parent::__construct();
    }

    /**
     * Returns the id of the docker container that is running the CodeCompass webserver
     *
     * @return string
     */
    public function getContainerId(): string
    {
        return $this->_containerId;
    }

    /**
     * Returns the errors that were generated when compiling the project with the given build script.
     *
     * @return string
     */
    public function getErrorLogs(): string
    {
        return $this->_errorLogs;
    }

    /**
     * Returns the one-time password that is used to sign in to the CodeCompass application.
     *
     * @return string
     */
    public function getCodeCompassPassword(): string
    {
        return $this->_codeCompassPassword;
    }

    /**
     * Returns the username that is used to sign in to the CodeCompass application.
     *
     * @return string
     */
    public function getCodeCompassUsername(): string
    {
        return $this->_codeCompassUsername;
    }

    /**
     * Starts the CodeCompass webserver.
     *  - First transfers the Submission to the container using a TAR stream.
     *  - Tries to compile the project and create compilation_commands.json that will be used for parsing.
     *  - Tries to parse the project using an SQLITE database.
     *  - Creates a one-time password for the user to sign in.
     *  - Deletes the build script
     *
     * @throws ErrorException
     */
    public function start()
    {
        if (!$this->extractStudentSolution()) {
            throw new ErrorException();
        }

        $container = $this->startContainerForTask();
        $this->createContainerRootDirectories($container);

        $this->createPackageInstallScript();
        $this->createBuildScript();
        $this->createAuthenticationFile();
        $this->transferProjectToContainer();

        $this->executeCommandAttached(['sed', '-i', 's/\x0D$//', self::BUILD_SCRIPT_FILE], $container);
        $this->executeCommandAttached(['sed', '-i', 's/\x0D$//', self::INSTALL_SCRIPT_FILE], $container);

        if ($this->_imageName == null && !empty($this->_submission->task->codeCompassPackagesInstallInstructions)) {
            $this->installBuildPackages($container);
            if (Yii::$app->params['codeCompass']['isImageCachingEnabled']) {
                $this->commitContainer($container);
            }
        }

        $this->executeCommandAttached(['mv', 'authentication.json', self::WORK_DIRECTORY . '/authentication.json'], $container);
        $this->executeCommandAttached(
            [
                'CodeCompass_logger',
                self::COMPILATION_COMMANDS_FILE_LOCATION,
                '/bin/bash ' . self::BUILD_SCRIPT_FILE
            ],
            $container,
            true
        );

        $this->executeCommandAttached(['rm', self::BUILD_SCRIPT_FILE], $container);
        $this->executeCommandAttached(['rm', self::INSTALL_SCRIPT_FILE], $container);

        $this->executeCommandAttached(
            [
                'CodeCompass_parser',
                '-d', self::SQLITE_DATA_FILE,
                '-w', self::WORK_DIRECTORY,
                '-n', $this->_submission->uploader->name . ' - ' . $this->_submission->task->name,
                '-i', self::PROJECT_DIRECTORY,
                '-i', self::COMPILATION_COMMANDS_FILE_LOCATION,
                '--label', 'src=/workspace'
            ],
            $container
        );
        $this->executeCommandDetached(['CodeCompass_webserver', '-w', self::WORK_DIRECTORY, '-p', '6251'], $container);
        $this->deleteStudentSolution();
    }

    /**
     * Stops and deletes the CodeCompass docker container.
     *
     * @throws ContainerDeleteBadRequestException
     * @throws ContainerDeleteNotFoundException
     * @throws ContainerDeleteConflictException
     * @throws ContainerDeleteInternalServerErrorException
     */
    public function stop()
    {
        $this->_docker->containerDelete($this->_containerId, ['force' => true]);
    }

    /**
     * Creates the main directories in the CodeCompass container
     *  - DATABASE_DIRECTORY: This is where the SQLITE file will be created.
     *  - WORK_DIRECTORY: This is where the parsed project will be placed.
     *  - COMPILE_COMMANDS_DIRECTORY: This is where the CodeCompass logger will place compilation_commands.json file.
     *
     * @param $container
     */
    private function createContainerRootDirectories($container)
    {
        $this->executeCommandAttached(['mkdir', self::DATABASE_DIRECTORY], $container);
        $this->executeCommandAttached(['mkdir', self::WORK_DIRECTORY], $container);
        $this->executeCommandAttached(['mkdir', self::COMPILE_COMMANDS_DIRECTORY], $container);
    }

    /**
     * Executes a `docker commit` command to cache the current running CodeCompass container.
     * This makes the start time significantly faster for all future instances.
     * This function will not get called if caching is disabled in params.php
     *
     * @param $container
     */
    private function commitContainer($container)
    {
        $this->_imageName = CodeCompassHelper::$CACHED_IMAGE_NAME_PREFIX . $this->_submission->taskID;

        $this->_docker->imageCommit($container->getConfig(), [
            'container' => $container->getId(),
            'repo' => $this->_imageName
        ]);
    }

    /**
     * Pulls the required image from the docker hub if it is not available yet.
     *
     * @param $imageName
     */
    private function pullCodeCompassImage($imageName)
    {
        /** @var CallbackStream $stream */
        $stream = $this->_docker->imageCreate('', [
            'fromImage' => $imageName,
        ]);
        $stream->wait();
    }

    /**
     * Installs the packages inside the container that are required to build the project.
     *
     * @param $container
     */
    private function installBuildPackages($container)
    {
        $this->executeCommandAttached(['apt-get', 'update', '-y'], $container);
        $this->executeCommandAttached(['/bin/bash', self::INSTALL_SCRIPT_FILE], $container);
    }

    /**
     * Creates a build BASH script that will be used to compile the project inside the container.
     */
    private function createBuildScript()
    {
        $buildScript = fopen(
            $this->_projectBasePath . '/' . self::BUILD_SCRIPT_FILE,
            'w'
        );
        fwrite($buildScript, $this->_submission->task->codeCompassCompileInstructions);
    }

    private function createPackageInstallScript()
    {
        $installScript = fopen(
            $this->_projectBasePath . '/' . self::INSTALL_SCRIPT_FILE,
            'w'
        );
        fwrite($installScript, $this->_submission->task->codeCompassPackagesInstallInstructions);
    }

    /**
     * Creates the authentication file that is required by CodeCompass
     * to access the authentication feature.
     */
    private function createAuthenticationFile()
    {
        $authenticationFile = fopen(
            $this->_projectBasePath . '/' . 'authentication.json',
            'w'
        );
        fwrite($authenticationFile, $this->makeAuthenticationJson());
    }

    /**
     * Extracts the uploaded ZIP file that contains the student solution.
     *
     * @return bool
     */
    private function extractStudentSolution(): bool
    {
        if (!file_exists($this->_projectBasePath)) {
            FileHelper::createDirectory($this->_projectBasePath, 0755, true);
        }

        $zip = new ZipArchive();
        $res = $zip->open($this->_submission->path);
        if ($res === true) {
            $zip->extractTo($this->_projectBasePath);
            $zip->close();
            return true;
        }
        return false;
    }

    /**
     * Creates a tar file from the student solution that can be transferred
     * to the docker container using a tar stream.
     */
    private function transferProjectToContainer()
    {
        $tarPath = $this->_projectBasePath . '/project.tar';
        $phar = new PharData($tarPath);
        $phar->buildFromDirectory($this->_projectBasePath);
        $this->_docker->putContainerArchive($this->_containerId, file_get_contents($tarPath), [
            'path' => self::PROJECT_DIRECTORY
        ]);
    }

    /**
     * Starts the docker container from the default image given in params.php or
     * by using a previously cached image for this task.
     *
     * @return ContainersIdJsonGetResponse200|ResponseInterface|null
     */
    private function startContainerForTask()
    {
        $imageName = $this->_imageName;
        if ($imageName == null) {
            $imageName = Yii::$app->params['codeCompass']['imageName'];
            $this->pullCodeCompassImage($imageName);
        }

        $containerConfig = new ContainersCreatePostBody();
        $containerConfig->setImage($imageName);
        $containerConfig->setTty(true);
        $containerConfig->setWorkingDir(self::PROJECT_DIRECTORY);
        $containerConfig->setCmd(['/bin/bash']);

        /** @var ArrayObject<string, stdClass> $ports*/
        $ports = new ArrayObject();
        $ports['6251/tcp'] = new stdClass();

        $containerConfig->setExposedPorts($ports);

        $portBinding = new PortBinding();
        $portBinding->setHostPort($this->_port);
        $portBinding->setHostIp('0.0.0.0');

        /** @var ArrayObject<string, array<int, PortBinding>> $portMap*/
        $portMap = new ArrayObject();
        $portMap['6251/tcp'] = [$portBinding];

        $hostConfig = new HostConfig();
        $hostConfig->setPortBindings($portMap);
        $containerConfig->setHostConfig($hostConfig);

        try {
            $this->_docker->containerCreate($containerConfig, ['name' => $this->_containerId]);
        } catch (\Exception $e) {
            $this->_docker->containerDelete($this->_containerId, ['force' => true]);
            $this->_docker->containerCreate($containerConfig, ['name' => $this->_containerId]);
        }

        $this->_docker->containerStart($this->_containerId);
        return $this->_docker->containerInspect($this->_containerId);
    }

    /**
     * Executes a command inside a container and waits for it to finish.
     *
     * @param array $commandDetails Contains the command that should be executed
     * @param $container
     * @param bool $shouldLogErrors If true, the content of the stderr will be appended to the
     * errorLogs property.
     */
    private function executeCommandAttached(array $commandDetails, $container, bool $shouldLogErrors = false)
    {
        $execCreateResult = $this->createCommandInsideContainer($container->getId(), $commandDetails, true);
        $execStartConfig = $this->createExecStartConfig(true);

        /** @var DockerRawStream $stream */
        $stream = $this->_docker->execStart(
            $execCreateResult->getId(),
            $execStartConfig
        );

        $stderrFull = '';
        $stream->onStderr(function ($stderr) use (&$stderrFull) {
            $stderrFull .= $stderr;
        });
        $stream->wait();

        if ($shouldLogErrors && $stderrFull != '') {
            if ($this->_errorLogs != '') {
                $this->_errorLogs .= "\n";
            }
            $this->_errorLogs .= implode(' ', $commandDetails) . ":\n";
            $this->_errorLogs .= $stderrFull;
        }
    }

    /**
     * Executes a command inside a container and does not wait it to finish.
     *
     * @param array $commandDetails
     * @param $container
     */
    private function executeCommandDetached(array $commandDetails, $container)
    {
        $execCreateResult = $this->createCommandInsideContainer($container->getId(), $commandDetails, false);
        $execStartConfig = $this->createExecStartConfig(false);

        $this->_docker->execStart(
            $execCreateResult->getId(),
            $execStartConfig
        );
    }

    /**
     * Helper method to create the Command object that will be used to execute a command inside
     * the container.
     *
     * @param string $containerId
     * @param array $commandDetails
     * @param bool $attached
     * @return IdResponse|ResponseInterface|null
     */
    private function createCommandInsideContainer(string $containerId, array $commandDetails, bool $attached)
    {
        $execConfig = new ContainersIdExecPostBody();
        $execConfig->setAttachStdout($attached);
        $execConfig->setAttachStderr($attached);
        $execConfig->setCmd($commandDetails);
        return $this->_docker->containerExec($containerId, $execConfig);
    }

    /**
     * Sets the config of the command that will be executed inside the container.
     *
     * @param bool $attached
     * @return ExecIdStartPostBody
     */
    private function createExecStartConfig(bool $attached): ExecIdStartPostBody
    {
        $execStartConfig = new ExecIdStartPostBody();
        $execStartConfig->setDetach(!$attached);
        $execStartConfig->setTty(false);
        return $execStartConfig;
    }

    /**
     * Deletes the student solution that was extracted to a temporary folder.
     *
     * @throws ErrorException
     */
    private function deleteStudentSolution()
    {
        if (is_dir($this->_projectBasePath)) {
            FileHelper::removeDirectory($this->_projectBasePath);
        }
    }

    /**
     * Returns a JSON string that will be used as the authentication.json inside
     * the docker container to enable the CodeCompass authentication feature.
     *
     * @return string
     */
    private function makeAuthenticationJson(): string
    {
        $this->_codeCompassUsername = Yii::$app->params['codeCompass']['username'];

        $passwordLength = Yii::$app->params['codeCompass']['passwordLength'];
        $this->_codeCompassPassword = implode(
            '',
            array_map(fn($i): int => rand(0, 9), range(0, $passwordLength - 1))
        );

        return /** @lang JSON */ <<<TEXT
        {
            "enabled": true,
            "prompt": "TMS - CodeCompass server login",
            "plain": {
                "enabled": true,
                "users": [
                    "$this->_codeCompassUsername:$this->_codeCompassPassword"
                ]
            }
        }
        TEXT;
    }
}
