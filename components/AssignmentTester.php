<?php

namespace app\components;

use app\components\docker\DockerImageManager;
use app\components\docker\EvaluatorTarBuilder;
use app\exceptions\EvaluatorTarBuilderException;
use app\models\TestResult;
use Yii;
use Docker\Docker;
use Docker\DockerClientFactory;
use Docker\API\Model\ContainersCreatePostBody;
use Docker\API\Model\ContainersIdExecPostBody;
use Docker\API\Model\ExecIdStartPostBody;
use ForceUTF8\Encoding;

/**
 *  This class implements the automatic tester using docker containers.
 */
class AssignmentTester
{
    /**
     * @property array An array containing the test results.
     */
    private $results;

    /**
     * @property \app\models\StudentFile The uploaded student solution.
     */
    private $studentFile;

    /**
     * @property \app\models\TestCase[] The test cases to be run.
     */
    private $testCases;

    /**
     * @property string The docker connection socket.
     */
    private $socket;

    /**
     * @property \Docker\Docker The docker connection.
     */
    private $docker;

    /**
     * Creates a Docker object maintaining the connection with a Docker daemon.
     *
     * By default, Docker-PHP uses the the same environment variables as the Docker command line to connect to a
     * running Docker daemon.
     *
     * @param string|null $socket the socket of the Docker daemon to connect
     * @return Docker
     */
    private static function connect($socket = null)
    {
        if (!empty($socket)) {
            return Docker::create(DockerClientFactory::create([
                'remote_socket' => $socket
            ]));
        } else {
            return Docker::create();
        }
    }

    /**
     *  Constructor
     *
     * @param \app\models\StudentFile $studentFile
     * @param \app\models\TestCase[] $testCases
     * @param string|null $socket the socket of the Docker daemon to connect
     */
    public function __construct($studentFile, $testCases, $socket = null)
    {
        $this->testCases = $testCases;
        $this->studentFile = $studentFile;
        $this->socket = $socket;
        $this->docker = self::connect($socket);
    }

    /**
     * @return array containing the test results.
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Transforms a string to be compliant with Docker naming.
     *
     * Replaces the Hungarian letters with English ones, removes whitespaces and non alphanumeric characters,
     * converts to lowercase.
     * @param string $s in which the letters will be replaced
     * @return string
     */
    public static function transformString($s)
    {
        $hu = ['/é/', '/É/', '/á/', '/Á/', '/ó/', '/Ó/', '/ö/', '/Ö/', '/ő/', '/Ő/', '/ú/', '/Ú/', '/ű/', '/Ű/', '/ü/', '/Ü/', '/í/', '/Í/', '/ /'];
        $en = ['e', 'E', 'a', 'A', 'o', 'O', 'o', 'O', 'o', 'O', 'u', 'U', 'u', 'U', 'u', 'U', 'i', 'I', '_'];
        $s = preg_replace('/\s+/', '-', $s);
        $s = preg_replace($hu, $en, $s);
        return strtolower($s);
    }

    /**
     * Runs the testCases on the studentfile.
     *
     * @return array an array with the test results.
     * @throws \Throwable
     */
    public function test(): void
    {
        $task = $this->studentFile->task;
        $imageName = $task->imageName;
        $containerName = $this->studentFile->containerName;

        $dockerImageManager = Yii::$container->get(DockerImageManager::class, ['os' => $task->testOS]);

        //create image for the task
        //$this->results = $dockerImageManager->buildImageForTask($imageName);

        // create a container from the image
        $this->createContainerForTask($imageName, $containerName);

        // start the container
        try {
            $this->docker->containerStart($containerName);
        } catch (\Exception $e) {
            // TODO: implement better logic for waiting on Docker containers to start on Windows with hyperv isolation
        }
        $container = $this->docker->containerInspect($containerName);

        // send student solution to docker container as TAR stream
        try {
            $this->copyFiles($containerName);
        } catch (\Exception $e) {
            $this->results['initialized'] = false;
            $this->results['initiationError'] = $e->getMessage();
            $this->stopContainer($containerName);
            return;
        }
        $this->results['initialized'] = true;

        // compile the student solution
        if ($task->testOS != 'windows') {
            $this->executeCommand(['chmod', '0755', '/test/compile.sh'], $container);
        }
        $compileCommand = [
            'timeout',
            strval(Yii::$app->params['evaluator']['compileTimeout']),
            '/bin/bash',
            '/test/compile.sh'
        ];
        if ($task->testOS == 'windows') {
            $compileCommand = ['powershell', 'C:\\test\\compile.ps1'];
        }
        $execResult = $this->executeCommand($compileCommand, $container);

        // check if the compilation was successful
        if ($execResult['exitCode'] != 0) {
            $this->results['compiled'] = false;
            if ($task->testOS == 'linux') {
                $this->results['compilationError'] = !empty($execResult['stderr']) ? $execResult['stderr'] : $execResult['stdout'];
            } else { // == 'windows'
                $this->results['compilationError'] = $execResult['stdout'];
            }
            $this->stopContainer($containerName);
            return;
        }
        $this->results['compiled'] = true;
        $this->results['executed'] = true;
        $this->results['passed'] = true;
        $this->results['errorMsg'] = '';
        $testCaseNr = 1;
        // run the test cases on the solution
        if ($task->testOS != 'windows') {
            $this->executeCommand(['chmod', '0755', '/test/run.sh'], $container);
        }

        foreach ($this->testCases as $testCase) {
            $result = $this->runTestCase($testCaseNr, $testCase, $container);
            if (!$this->checkResult($result, $testCaseNr, $testCase) && $this->results['passed']) {
                // The overall result will be the status of the first failing test case.
                $this->results['passed'] = false;
                $this->results['executed'] = $this->results[$testCaseNr]['executed'];
                $this->results['errorMsg'] = $this->results[$testCaseNr]['errorMsg'];
            }

            $testCaseNr++;
        }

        $this->stopContainer($containerName);
    }

    /**
     * Send student solution, test files and scripts to docker container as TAR stream
     * @param string $containerName
     * @return void
     * @throws EvaluatorTarBuilderException
     */
    private function copyFiles(string $containerName)
    {
        $tarBuilder = new EvaluatorTarBuilder(Yii::getAlias("@appdata/tmp/docker/"), strval($this->studentFile->id));
        $task = $this->studentFile->task;
        $ext = $task->testOS == 'windows' ? '.ps1' : '.sh';
        try {
            $tarPath = $tarBuilder
                ->withSubmission($this->studentFile->getPath())
                ->withInstructorTestFiles($task->id)
                ->withTextFile('compile' . $ext, $task->compileInstructions, true)
                ->withTextFile('run' . $ext, $task->runInstructions, true)
                ->buildTar();

            // The container must be stopped before uploading files when a Windows host with Hyper-V isolation is configured
            $sysInfo = $this->docker->systemInfo();
            $shouldStop = $sysInfo->getOSType() == 'windows' && $sysInfo->getIsolation() == 'hyperv';
            if ($shouldStop) {
                $this->docker->containerStop($containerName);
            }

            $this->docker->putContainerArchive(
                $containerName,
                file_get_contents($tarPath),
                [
                    'path' => $task->testOS == 'windows' ? 'C:\\test' : '/test'
                ]
            );

            if ($shouldStop) {
                try {
                    $this->docker->containerStart($containerName);
                } catch (\Exception $e) {
                    // TODO: implement better logic for waiting on Docker containers to start on Windows with hyperv isolation
                }
                $container = $this->docker->containerInspect($containerName);
            }
        } finally {
            $tarBuilder->cleanup();
        }
    }

    /**
     * Checks the result of one test case and populates the results array
     *
     * @param array $result the result of the execution
     * @param int $testCaseNr
     * @param \app\models\TestCase $testCase
     *
     * @return bool whether the student solution passed the test case
     */
    private function checkResult($result, $testCaseNr, $testCase)
    {
        $task = $this->studentFile->task;
        // check if there were errors during the execution
        if ($result['exitCode'] != 0) {
            $this->results[$testCaseNr]['executed'] = false;
            $this->results[$testCaseNr]['passed'] = false;
            // If the execution timed out
            if ($result['exitCode'] == 124 && $task->testOS == 'linux') {
                $this->results[$testCaseNr]['errorMsg'] = Yii::t('app', 'Your solution exceeded the execution time limit.');
            } elseif ($task->testOS == 'linux') {
                $this->results[$testCaseNr]['errorMsg'] = !empty($result['stderr']) ? $result['stderr'] : $result['stdout'];
            } else { // == 'windows'
                $this->results[$testCaseNr]['errorMsg'] = $result['stdout'];
            }
            return false;
        }

        $this->results[$testCaseNr]['executed'] = true;
        $this->results[$testCaseNr]['arguments'] = $testCase->arguments;
        $this->results[$testCaseNr]['input'] = $testCase->input;
        $this->results[$testCaseNr]['expectedOutput'] = $testCase->output;
        $this->results[$testCaseNr]['output'] = $result['stdout'];
        $this->results[$testCaseNr]['errorMsg'] = null;

        // check if the output matches the expected output
        if ($result['equal'] === 0) {
            $this->results[$testCaseNr]['passed'] = true;
        } else {
            $this->results[$testCaseNr]['errorMsg'] = Yii::t('app', 'Your solution failed on') . ':' . PHP_EOL .
                Yii::t('app', 'Command arguments') . ': ' . PHP_EOL .
                $testCase->arguments . PHP_EOL . PHP_EOL .
                Yii::t('app', 'Given input') . ': ' . PHP_EOL .
                $testCase->input . PHP_EOL . PHP_EOL .
                Yii::t('app', 'Expected output') . ': ' . PHP_EOL .
                $testCase->output . PHP_EOL . PHP_EOL .
                Yii::t('app', 'Actual output') . ': ' . PHP_EOL .
                $result['stdout'];
            $this->results[$testCaseNr]['passed'] = false;
            return false;
        }
        return true;
    }

    /**
     * Stops the container and cleans up the files.
     *
     * @param string $containerName The name of the container
     */
    private function stopContainer($containerName)
    {
        // stop the container
        $this->docker->containerStop($containerName);

        // remove the container
        $this->docker->containerDelete($containerName);
    }

    /**
     * Runs a test case in the container
     *
     * @param int $testCaseNr
     * @param \app\models\TestCase $testCase
     * @param \Docker\API\Model\ContainersIdJsonGetResponse200 $container
     */
    private function runTestCase($testCaseNr, $testCase, $container)
    {
        $task = $this->studentFile->task;

        // runs the compiled program with the testCase input redirected to its stdin
        // set TEST_CASE_NR environment variable
        if ($task->testOS == 'linux') {
            $runCommand = [
                'timeout',
                strval(Yii::$app->params['evaluator']['testTimeout']),
                '/bin/bash',
                '-c',
                "TEST_CASE_NR=$testCaseNr /test/run.sh $testCase->arguments <<< \"{$testCase->input}\""
            ];
        } else { // $task->testOS == 'windows'
            $runCommand = [
                "powershell",
                "-Command",
                "\$env:TEST_CASE_NR=$testCaseNr; echo \"{$testCase->input}\" | powershell C:\\test\\run.ps1 $testCase->arguments"
            ];
        }
        $execResult = $this->executeCommand($runCommand, $container);

        // trimming expected and actual output
        $actualOutput = trim($execResult['stdout']);
        $expectedOutput = trim($testCase->output);
        // removing /r from output preventing errors from newline mismatches
        $actualOutput = preg_replace('/\r/', '', $actualOutput);
        $expectedOutput = preg_replace('/\r/', '', $expectedOutput);
        // remove the utf-8 BOM (added by powershell)
        $actualOutput = preg_replace('/^\xEF\xBB\xBF/', '', $actualOutput);

        // Check for output equality
        $execResult['equal'] = strcmp($actualOutput, $expectedOutput);
        return $execResult;
    }

    /**
     * Creates a container for the task
     *
     * @param string $imageName The name of the image from which the container is going to be created
     * @param string $containerName The name of the container to be created
     */
    private function createContainerForTask($imageName, $containerName)
    {
        // set up the image for being able to run tests
        $containerConfig = new ContainersCreatePostBody();
        $containerConfig->setImage($imageName);
        $containerConfig->setTty(true);
        if ($this->studentFile->task->testOS == 'windows') {
            $containerConfig->setWorkingDir('C:\\test\\submission');
            $containerConfig->setCmd(['powershell']);
        } else {
            $containerConfig->setWorkingDir('/test/submission');
            $containerConfig->setCmd(['/bin/bash']);
        }

        // create the container
        try {
            $containerCreateResult = $this->docker->containerCreate($containerConfig, ['name' => $containerName]);
        } catch (\Exception $e) {
            $this->docker->containerStop($containerName);
            $this->docker->containerDelete($containerName);
            $containerCreateResult = $this->docker->containerCreate($containerConfig, ['name' => $containerName]);
        }

        return $containerCreateResult;
    }

    /**
     *  Executes a command in a running container.
     *
     * @param array $commandDetails an array containing the command and it's parameters. For example ['g++', 'main.cpp']
     * @param \Docker\API\Model\ContainersIdJsonGetResponse200 $container the running docker container.
     *
     * @return array containing the stdout, stderr logs and the exit code.
     */
    private function executeCommand($commandDetails, $container)
    {
        $execConfig = new ContainersIdExecPostBody();
        $execConfig->setAttachStdout(true);
        $execConfig->setAttachStderr(true);
        $execConfig->setCmd($commandDetails);

        $execCreateResult = $this->docker->containerExec($container->getId(), $execConfig);

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
            if (mb_strlen($stdoutFull) + mb_strlen($stdout) < 1024 * 1024) { // 1 MB should be enough
                $stdoutFull .= $stdout;
            } else {
                throw new \OverflowException(Yii::t('app', 'Your solution exceeded the maximum output size.'));
            }
        });
        $stream->onStderr(function ($stderr) use (&$stderrFull) {
            if (mb_strlen($stderrFull) + mb_strlen($stderr) < 65000) { // StudentFile::errorMsg field is 65535 in size
                $stderrFull .= $stderr;
            } else {
                throw new \OverflowException(Yii::t('app', 'Your solution exceeded the maximum error output size.'));
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

        return [
            'stdout' => Encoding::toUTF8($stdoutFull),
            'stderr' => Encoding::toUTF8($stderrFull),
            'exitCode' => $exitCode
        ];
    }
}
