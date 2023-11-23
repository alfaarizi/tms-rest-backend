<?php

namespace app\components\docker;

use app\exceptions\DockerContainerException;
use Yii;

/**
 * Docker container desi
 */
class WebTesterContainer
{
    public const SUITES_DIR_NAME = 'suites';
    public const REPORTS_DIR_NAME = 'reports';

    /**
     * Builds the container working dir path
     * @param string $os
     * @param string|null $subDir optional subdirectory to the working dir
     * @return string
     */
    private static function getWorkingDir(string $os, string $subDir = null): string
    {
        $base = $os == 'linux' ? '/home/pwuser' : 'C:\\Users\\pwuser';
        if (!empty($subDir)) {
            return $base . ($os == 'linux' ? "/$subDir" : "\\$subDir");
        }
        return $base;
    }

    /**
     * The Docker image name of the test runner container
     * @param string $os
     * @return string
     * @throws \Exception
     */
    public static function getTesterImageName(string $os): string
    {
        if ($os == 'windows') {
            //TODO: add support for windows, manage directories, etc
            throw new \Exception("Windows web apps not yet supported");
        }
        return Yii::$app->params['evaluator']['webApp'][$os]['robotFrameworkImage'];
    }

    /**
     * Creates an instance of the WebTestContainer to validate test scripts
     *
     * @param string $os
     * @return WebTesterContainer
     * @throws \yii\base\Exception
     * @throws DockerContainerException
     */
    public static function createInstanceForValidation(string $os): WebTesterContainer
    {
        $workingDir = WebTesterContainer::getWorkingDir($os);
        $builder = new DockerContainerBuilder($os, WebTesterContainer::getTesterImageName($os));
        $container = $builder
            ->withWorkingDir($workingDir)
            ->withEnv('PATH', '/home/pwuser/.local/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"')
            ->withEnv('NODE_PATH', '/usr/lib/node_modules')
            ->build();

        return new WebTesterContainer($os, $workingDir, $container);
    }

    /**
     * Creates an instance of the WebTestContainer to run test suites
     *
     * @param string $os
     * @param DockerContainer $applicationUnderTest
     * @param string $webAppPort
     * @return WebTesterContainer
     * @throws \yii\base\Exception
     */
    public static function createInstanceForTest(string $os, DockerContainer $applicationUnderTest, string $webAppPort): WebTesterContainer
    {
        $workingDir = WebTesterContainer::getWorkingDir($os);
        $builder = new DockerContainerBuilder($os, WebTesterContainer::getTesterImageName($os));
        $container = $builder
            ->withWorkingDir($workingDir)
            ->withEnv('PATH', '/home/pwuser/.local/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"')
            ->withEnv('NODE_PATH', '/usr/lib/node_modules')
            //for tests to access the web server port on localhost
            ->withEnv('WEB_APP_PORT', $webAppPort)
            // use the same network stack as the sut -> test can access sut via localhost
            ->withNetworkMode('container:' . $applicationUnderTest->getContainerInspectResult()['Id'])
            ->build();

        return new WebTesterContainer($os, $workingDir, $container);
    }

    private DockerContainer $testRunner;
    private string $workingDir;
    private string $os;

    private function __construct(string $os, string $workingDir, DockerContainer $testRunner)
    {
        $this->os = $os;
        $this->workingDir = $workingDir;
        $this->testRunner = $testRunner;
    }

    /**
     * Uploads and runs the given test suites
     * @param string $testsPath path to the tar file with the suites. The tar must contain a directory named: 'suites'.
     * @return array|null results of execution
     */
    public function runTests(string $testsPath): ?array
    {
        $this->testRunner->uploadArchive($testsPath, $this->workingDir);

        $this->testRunner->startContainer();

        return $this->testRunner->executeCommand(
            [
                'robot',
                '--outputdir',
                WebTesterContainer::getWorkingDir($this->os, WebTesterContainer::REPORTS_DIR_NAME),
                '--xunit',
                'xunit.xml',
                '--exitonerror',
                '--exitonfailure',
                '--quiet',
                WebTesterContainer::getWorkingDir($this->os, WebTesterContainer::SUITES_DIR_NAME)
            ]
        );
    }

    /**
     * Uploads and validates a test scripts
     * @param string $scriptPath path to the tar file with the script
     * @return array|null validation result containing the <code>stdout</code>, <code>stderr</code> logs and the <code>exitCode</code>.
     */
    public function validateTestScript(string $scriptPath): ?array
    {
        $this->testRunner->uploadArchive($scriptPath, $this->workingDir);
        $this->testRunner->startContainer();
        return $this->testRunner->executeCommand(
            [
                'robot',
                '--dryrun',
                '--report', 'None',
                '--log', 'None',
                '--output', 'None',
                './suite.robot'
            ]
        );
    }

    /**
     * Downloads test reports to the given path
     * @param string $destinationDir
     * @return void
     */
    public function downloadTestReports(string $destinationDir)
    {
        $this->testRunner->downloadArchive(
            WebTesterContainer::getWorkingDir($this->os, WebTesterContainer::REPORTS_DIR_NAME),
            $destinationDir
        );
    }

    /**
     * Deletes the underlying container
     * @return void
     */
    public function tearDown()
    {
        $this->testRunner->stopContainer();
    }

    /**
     * The name of the underlying container
     * @return string
     */
    public function getContainerName(): string
    {
        return $this->testRunner->getContainerName();
    }
}
