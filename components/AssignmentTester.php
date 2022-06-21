<?php

namespace app\components;

use app\models\InstructorFile;
use Yii;
use Docker\Docker;
use Docker\DockerClientFactory;
use Docker\Context\Context;
use Docker\API\Model\ContainersCreatePostBody;
use Docker\API\Model\BuildInfo;
use Docker\API\Model\ContainersIdExecPostBody;
use Docker\API\Model\ExecIdStartPostBody;
use yii\helpers\FileHelper;
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
     */
    public function test()
    {
        // get student solution
        $this->extractStudentSolution();
        // copy test files
        $this->copyTestFiles();

        $task = $this->studentFile->task;
        $imageName = $task->imageName;
        $containerName = $task->containerName;

        //create image for the task
        //$this->results = self::buildImageForTask($imageName);

        // if no image is created, return
        if (!$this->alreadyBuilt($imageName, $this->socket)) {
            return;
        }

        // create a container from the image
        $this->createContainerForTask($imageName, $containerName);

        // start the container
        $this->docker->containerStart($containerName);
        $container = $this->docker->containerInspect($containerName);

        // send student solution to docker container as TAR stream
        $tarPath = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/tmp/docker/test_' .  $this->studentFile->id . '.tar';
        $phar = new \PharData($tarPath);
        $phar->buildFromDirectory(Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/tmp/docker/');
        $this->docker->putContainerArchive(
            $containerName,
            file_get_contents($tarPath),
            [
                'path' => $task->testOS == 'windows' ? 'C:\\test' : '/test'
            ]
        );

        // compile the student solution
        $compileCommand = [
            'timeout',
            Yii::$app->params['evaluator']['compileTimeout'],
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
            if ($task->testOS == 'windows') {
                $this->results['compilationError'] = $execResult['stdout'] . PHP_EOL . $execResult['stderr'];
            } else {
                $this->results['compilationError'] = $execResult['stderr'];
            }
            $this->stopContainer($containerName);
            return;
        }
        $this->results['compiled'] = true;
        $this->results['passed'] = true;
        $this->results['error'] = false;
        $this->results['errorMsg'] = '';
        $testCaseNr = 1;
        // run the test cases on the solution
        foreach ($this->testCases as $testCase) {
            $result = $this->runTestCase($testCaseNr, $testCase, $container);
            if (!$this->checkResult($result, $testCaseNr, $testCase)) {
                $this->stopContainer($containerName);
                return;
            }
            $testCaseNr++;
        }

        $this->stopContainer($containerName);
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
            $this->results['passed'] = false;
            $this->results['error'] = true;
            // If the execution timed out
            if ($result['exitCode'] == 124 && $task->testOS == 'linux') {
                $this->results['errorMsg'] = Yii::t('app', 'Your solution exceeded the execution time limit.');
            } else {
                $this->results['errorMsg'] = $result['stderr'];
            }
            return false;
        }
        $this->results[$testCaseNr]['arguments'] = $testCase->arguments;
        $this->results[$testCaseNr]['input'] = $testCase->input;
        $this->results[$testCaseNr]['expectedOutput'] = $testCase->output;
        $this->results[$testCaseNr]['output'] = $result['stdout'];
        // check if the output matches the expected output
        if ($result['equal'] === 0) {
            $this->results[$testCaseNr]['passed'] = true;
        } else {
            $this->results['errorMsg'] = Yii::t('app', 'Your solution failed on') . ':' . PHP_EOL .
                Yii::t('app', 'Command arguments') . ': ' . PHP_EOL .
                $testCase->arguments . PHP_EOL . PHP_EOL .
                Yii::t('app', 'Given input') . ': ' . PHP_EOL .
                $testCase->input . PHP_EOL . PHP_EOL .
                Yii::t('app', 'Expected output') . ': ' . PHP_EOL .
                $testCase->output . PHP_EOL . PHP_EOL .
                Yii::t('app', 'Actual output') . ': ' . PHP_EOL .
                $result['stdout'];
            $this->results[$testCaseNr]['passed'] = false;
            $this->results['passed'] = false;
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

        // delete the student solution
        $this->deleteStudentSolution();
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

        // runs the compiled program with the testCase input redirected to it's stdin
        // set TEST_CASE_NR environment variable
        $runCommand = [
            'timeout',
            Yii::$app->params['evaluator']['testTimeout'],
            '/bin/bash',
            '-c',
            "TEST_CASE_NR=$testCaseNr /test/run.sh $testCase->arguments <<< \"{$testCase->input}\""];
        if ($task->testOS == 'windows') {
            $runCommand = [
                "powershell echo \"{$testCase->input}\" | ",
                "powershell \$TEST_CASE_NR=$testCaseNr; C:\\test\\run.ps1 $testCase->arguments"];
        }
        $execResult = $this->executeCommand($runCommand, $container);

        // Check for output equality
        // trimming expected and actual output
        // removing /r from output preventing errors from newline mismatches
        $execResult['equal'] = strcmp(
            preg_replace('/\r/', '', trim($execResult['stdout'])),
            preg_replace('/\r/', '', trim($testCase->output))
        );
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

        // add compile commands file
        $task = $this->studentFile->task;
        $compileFile = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/tmp/docker/compile.' .
            ($this->studentFile->task->testOS == 'windows' ? 'ps1' : 'sh');
        file_put_contents($compileFile, $task->compileInstructions);
        chmod($compileFile, 0755);

        // add run command file
        if (!empty($task->runInstructions)) {
            $runFile = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/tmp/docker/run.' .
                ($this->studentFile->task->testOS == 'windows' ? 'ps1' : 'sh');
            file_put_contents($runFile, $task->runInstructions);
            chmod($runFile, 0755);
        }

        return $containerCreateResult;
    }

    /**
     * Extracts the student solution to tmp/docker/submission/
     *
     * @return bool The success of extraction.
     */
    private function extractStudentSolution()
    {
        $path = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/tmp/docker/submission/';

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $zip = new \ZipArchive();
        $res = $zip->open($this->studentFile->path);
        if ($res === true) {
            $zip->extractTo($path);
            $zip->close();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Copies the instructor defined test files of the task to tmp/docker/test_files/
     *
     * @return bool The success of the copy operations.
     */
    private function copyTestFiles()
    {
        $path = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/tmp/docker/test_files/';

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $testFiles = InstructorFile::find()
            ->where(['taskID' => $this->studentFile->taskID])
            ->onlyTestFiles()
            ->all();

        $success = true;
        foreach ($testFiles as $testFile) {
            $success &= copy($testFile->path, $path . '/' . $testFile->name);
        }
        return $success;
    }

    /**
     *  Deletes the student solution and related files from tmp/docker/
     */
    private function deleteStudentSolution()
    {
        $path = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/tmp/docker/';
        if (is_dir($path)) {
            FileHelper::removeDirectory($path);
        }
    }

    /**
     * Checks if an image have already been built.
     *
     * @param string $imageName the name of the image.
     * @param string|null $socket the socket of the Docker daemon to connect.
     *
     * @return bool
     */
    public static function alreadyBuilt($imageName, $socket = null)
    {
        $docker = self::connect($socket);
        /* @var \Docker\API\Model\ImageSummary[] $images */
        $images = $docker->imageList();
        foreach ($images as $image) {
            $tags = $image->getRepoTags();
            if (!is_array($tags)) {
                continue;
            }
            foreach ($tags as $tag) {
                if (strcmp($imageName, $tag) === 0) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Removes a docker image
     *
     * @param string $imageName the name of the image
     * @param string|null $socket the socket of the Docker daemon to connect
     */
    public static function removeImage($imageName, $socket = null)
    {
        $docker = self::connect($socket);
        $docker->imageDelete($imageName);
    }

    /**
     * Downloads a docker image
     *
     * @param string $imageName the name of the image
     * @param string|null $socket the socket of the Docker daemon to connect
     */
    public static function pullImage($imageName, $socket = null)
    {
        $docker = self::connect($socket);
        /** @var \Docker\Stream\CallbackStream $createStream */
        $createStream = $docker->imageCreate('', [
            'fromImage' => $imageName,
        ]);

        $createStream->onFrame(function ($createImageInfo) use (&$firstMessage): void {
            if (null === $firstMessage) {
                $firstMessage = $createImageInfo->getStatus();
            }
        });
        $createStream->wait();
    }

    /**
     *  Checks if the image have already been built for the task, if not, builds it.
     *
     * @param string $taskName the name of the image
     * @param string $path the path to the Dockerfile
     * @param string|null $socket the socket of the Docker daemon to connect
     *
     * @return array an associative array containing the success and log of the build
     */
    public static function buildImageForTask($taskName, $path, $socket = null)
    {
        $buildLog = "";
        $buildResult = [];
        $buildResult['success'] = true;
        $buildResult['error'] = '';
        if (!self::alreadyBuilt($taskName, $socket)) {
            $context = new Context($path);
            $inputStream = $context->toStream();
            $docker = self::connect($socket);
            /** @var \Docker\Stream\CallbackStream $buildStream */
            $buildStream = $docker->imageBuild($inputStream, ['t' => $taskName, 'nocache' => true]);
            $buildStream->onFrame(function (BuildInfo $buildInfo) use ($buildLog) {
                // Log the build info
                $buildLog .= $buildInfo->getStream();
                // if there were errors, return error
                if ($buildInfo->getError()) {
                    $buildResult['success'] = false;
                    $buildResult['error'] = $buildInfo->getError();
                }
            });
            $buildStream->wait();
        }
        $buildResult['log'] = $buildLog;
        return $buildResult;
    }

    /**
     * Fetches the image information from an image
     *
     * @param string $imageName name of the image
     * @param string|null $socket String socket of the Docker daemon to connect
     * @return \Docker\API\Model\Image
     */
    public static function inspectImage(string $imageName, string $socket = null): \Docker\API\Model\Image
    {
        $docker = self::connect($socket);
        /** @var \Docker\API\Model\Image $imageInfo */
        return $docker->imageInspect($imageName);
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
}
