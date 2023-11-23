<?php

namespace app\modules\instructor\components;

use app\components\docker\DockerContainer;
use app\components\SubmissionRunner;
use app\exceptions\SubmissionRunnerException;
use app\models\StudentFile;
use app\models\Task;
use app\models\WebAppExecution;
use app\modules\instructor\components\exception\WebAppExecutionException;
use app\modules\instructor\resources\SetupWebAppExecutionResource;
use app\modules\instructor\resources\WebAppExecutionResource;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Exception;
use Yii;
use yii\base\ErrorException;
use yii\db\Transaction;
use yii\helpers\FileHelper;

class WebAppExecutor
{
    /**
     * Checks if the docker engine is hosted on the local server.
     * @param string $os
     * @return bool
     */
    public static function isDockerHostLocal(string $os): bool
    {
        $dockerHost = Yii::$app->params['evaluator'][$os];
        if (1 == preg_match(
                '/(unix:\/\/\/var\/run\/docker.sock|tcp:\/\/127.0.0.1|tcp:\/\/localhost)/i',
                $dockerHost)) {
            return true;
        }

        $dockerUrl = [];
        preg_match('/(?<=(tcp:\/\/))[\w.]+/', $dockerHost, $dockerUrl);
        $backendUrl = [];
        preg_match('/(?<=(http:\/\/)|(https:\/\/))[\w.]+/',
                   Yii::$app->params['backendUrl'], $backendUrl);

        if ($dockerUrl[0] == $backendUrl[0]) {
            return true;
        }
        return false;
    }

    private SubmissionRunner $submissionRunner;

    /**
     * @throws \yii\di\NotInstantiableException
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct(SubmissionRunner $submissionRunner = null)
    {
        if (empty($submissionRunner)) {
            Yii::debug('Initializing with dependency injection', __METHOD__);
            $this->submissionRunner = Yii::$container->get(SubmissionRunner::class);
        } else {
            $this->submissionRunner = $submissionRunner;
        }
    }

    /**
     * Starts a web application accessible on a host port.
     *
     * @param StudentFile $studentFile the student file to launch
     * @param int $userID instructor's ID
     * @param SetupWebAppExecutionResource $setupData configuration parameters
     *
     * @return WebAppExecutionResource
     * @throws WebAppExecutionException
     * @throws Exception
     */
    public function startWebApplication(StudentFile $studentFile, int $userID, SetupWebAppExecutionResource $setupData): WebAppExecutionResource
    {
        $this->validate($studentFile, $userID);

        $remoteExecution = $this->reservePort($studentFile, $userID);

        try {
            $dockerContainer = $this->submissionRunner->run($studentFile, $remoteExecution->port);
            $remoteExecution->containerName = $dockerContainer->getContainerName();
        } catch (SubmissionRunnerException $e) {
            $remoteExecution->delete();
            switch ($e->getCode()) {
                case SubmissionRunnerException::PREPARE_FAILURE:
                    $this->processPrepareFailure($studentFile, $e);
                    $errorMsg = Yii::t('app', 'Container start failed while processing files.');
                    break;
                case SubmissionRunnerException::COMPILE_FAILURE:
                    $this->processCompileFailure($studentFile, $e);
                    $errorMsg = Yii::t('app', 'Container started failed while compiling student submission.');
                    break;
                case SubmissionRunnerException::RUN_FAILURE:
                    $errorMsg = Yii::t('app', 'Container started failed while executing run instructions.');
                    break;
                default:
                    $errorMsg = Yii::t('app', 'Container start failed with unknown reason.');
            }
            throw new WebAppExecutionException($errorMsg, WebAppExecutionException::$START_UP_FAILURE, $e);
        } catch (Exception $e) {
            $remoteExecution->delete();
            throw $e;
        }

        $now = new DateTime();
        $remoteExecution->startedAt = $now->format(DateTimeInterface::ATOM);
        $remoteExecution->shutdownAt = $now
            ->add(new DateInterval('PT' . $setupData->runInterval . 'M'))
            ->format(DateTimeInterface::ATOM);
        $remoteExecution->save();

        return $remoteExecution;
    }

    /**
     * Shuts down a web app execution
     *
     * @param WebAppExecution $webAppExecution
     * @return void
     * @throws WebAppExecutionException
     * @throws \yii\base\InvalidConfigException
     */
    public function stopWebApplication(WebAppExecution $webAppExecution)
    {
        $dockerContainer = DockerContainer::createForRunning($webAppExecution->studentFile->task->testOS,
                                                             $webAppExecution->containerName);

        if (!empty($dockerContainer)) {
            try {
                $dockerContainer->stopContainer();
            } catch (\Exception $e) {
                Yii::error(
                    "Failed to stop container of WebAppExecution [$webAppExecution->id]" . $e->getMessage() . ' ' . $e->getTraceAsString());
                throw new WebAppExecutionException(Yii::t("app", "Failed to stop container."), WebAppExecutionException::SHUTDOWN_FAILURE, $e);
            }
        }

        Yii::$app->db->transaction(function($db) use ($webAppExecution) {
            $webAppExecution->delete();
        }, Transaction::SERIALIZABLE);
    }

    /**
     * Validates preconditions before starting the container
     *
     * @throws WebAppExecutionException
     */
    private function validate(StudentFile $studentFile, int $userID)
    {
        $remoteExecution = WebAppExecutionResource::find()->executionsOf($studentFile, $userID)->one();
        if (!is_null($remoteExecution)) {
            Yii::info(
                "Won\'t start web app for user [$userID] with id [$remoteExecution->id] of studentFile [$studentFile->id]");
            throw new WebAppExecutionException(Yii::t('app', 'An instance is already running or scheduled'), WebAppExecutionException::$PREPARATION_FAILURE);
        }

        if ($studentFile->task->appType != Task::APP_TYPE_WEB) {
            Yii::error(
                "Only [Web] Task types are executable studentFile [$studentFile->id] is og type [$studentFile->task->appType]");
            throw new WebAppExecutionException(Yii::t('app', 'Only Web application task types are executable.'), WebAppExecutionException::$PREPARATION_FAILURE);
        }

        if ($studentFile->autoTesterStatus == StudentFile::AUTO_TESTER_STATUS_COMPILATION_FAILED) {
            Yii::info("Won\'t start web for student file [$studentFile->id] because latest compilation failed.");
            throw new WebAppExecutionException(Yii::t('app', 'The latest submission failed to compile.'), WebAppExecutionException::$PREPARATION_FAILURE);
        }
    }

    /**
     * Reserve a host port for the web app
     * <p>
     * Reservation is basically inserting a new database record with a not yet allocated port number.
     * The transaction isolation is serializable so to prevent race condition when multiple web applications launching at the same time.
     *
     * @param StudentFile $studentFile
     * @param int $userID
     * @return WebAppExecutionResource
     * @throws Exception
     */
    private function reservePort(StudentFile $studentFile, int $userID): WebAppExecutionResource
    {
        $remoteExecutionResource = new WebAppExecutionResource();
        $remoteExecutionResource->studentFileID = $studentFile->id;
        $remoteExecutionResource->instructorID = $userID;
        $remoteExecutionResource->dockerHostUrl = $this->getDockerHostUrl($studentFile->task->testOS);

        $reservablePorts = [];
        if (!empty(Yii::$app->params['evaluator']['webApp'][$studentFile->task->testOS]['reservedPorts'])) {
            $reservablePorts = range(
                Yii::$app->params['evaluator']['webApp'][$studentFile->task->testOS]['reservedPorts']['from'],
                Yii::$app->params['evaluator']['webApp'][$studentFile->task->testOS]['reservedPorts']['to']
            );
        }
        if (empty($reservablePorts)) {
            throw new WebAppExecutionException(Yii::t('app', 'Platform not supported for web application testing.'), WebAppExecutionException::$PREPARATION_FAILURE);
        }

        Yii::$app->db->transaction(function($db) use ($remoteExecutionResource, $reservablePorts) {
            $reservedPorts = array_map(function ($model) {
                return $model->port;
            },
                WebAppExecutionResource::find()
                    ->select('port')
                    ->where(['dockerHostUrl' => $remoteExecutionResource->dockerHostUrl])
                    ->all()
            );

            $reservablePorts = array_values(
                array_diff(
                    $reservablePorts,
                    $reservedPorts
                )
            );
            if (empty($reservablePorts)) {
                Yii::info(
                    "All web app ports reserved at the moment, can\'t start web app for studentFile [$remoteExecutionResource->studentFileID]");
                throw new WebAppExecutionException(Yii::t('app', 'All ports reserved at the moment.'), WebAppExecutionException::$PREPARATION_FAILURE);
            }
            $remoteExecutionResource->port = $reservablePorts[0];
            $remoteExecutionResource->save();

        }, Transaction::SERIALIZABLE);

        return $remoteExecutionResource;
    }

    /**
     * Store initialization failure
     *
     * @param StudentFile $studentFile
     * @param SubmissionRunnerException $e
     * @return void
     */
    private function processPrepareFailure(StudentFile $studentFile, SubmissionRunnerException $e)
    {
        $errorMsg = !is_null($e->getPrevious()) ? $e->getPrevious()->getMessage() : $e->getMessage();
        $studentFile->isAccepted = StudentFile::IS_ACCEPTED_FAILED;
        $studentFile->autoTesterStatus = StudentFile::AUTO_TESTER_STATUS_INITIATION_FAILED;
        $studentFile->errorMsg = $errorMsg;
        $studentFile->save();
    }

    /**
     * Store compilation failure
     *
     * @param StudentFile $studentFile
     * @param SubmissionRunnerException $e
     * @return void
     */
    private function processCompileFailure(StudentFile $studentFile, SubmissionRunnerException $e)
    {
        //TODO: should update conditionally based on previous values
        $errorMsg = (!empty($e->getStdout()) ? $e->getStdout() . PHP_EOL : '') . $e->getStderr();
        $studentFile->isAccepted = StudentFile::IS_ACCEPTED_FAILED;
        $studentFile->autoTesterStatus = StudentFile::AUTO_TESTER_STATUS_COMPILATION_FAILED;
        $studentFile->errorMsg = $errorMsg;
        $studentFile->save();
    }

    private function getDockerHostUrl(string $os)
    {
        if (WebAppExecutor::isDockerHostLocal($os)) {
            $url = Yii::$app->params['backendUrl'];
            $regex = '/^http[s]?:\/\/[\w.]+/';
        } else {
            $url = Yii::$app->params['evaluator'][$os];
            $regex = '/^tcp:\/\/[\w.]+/';
        }
        $base = [];
        preg_match($regex, $url, $base);
        return $base[0];
    }

    /**
     * Extracts the run instructions log from the webapp execution docker instance
     * @param WebAppExecution $webAppExecution
     * @return string contents of the run log
     * @throws \yii\base\InvalidConfigException
     * @throws ErrorException
     */
    public function fetchRunLog(WebAppExecution $webAppExecution): string
    {
        $tmpPath = null;
        try {
            $os = $webAppExecution->studentFile->task->testOS;
            $container = DockerContainer::createForRunning($os, $webAppExecution->containerName);

            $pathInContainer = SubmissionRunner::getWebappRunLogPath($os);
            $containerName = $container->getContainerName();
            $folderName = $containerName . '_' . Yii::$app->security->generateRandomString(4);
            $tmpPath = Yii::getAlias("@appdata/tmp/webapprunlog/$folderName");
            mkdir($tmpPath, 0755, true);

            $tarPath = "$tmpPath/log.tar";
            $container->downloadArchive($pathInContainer , $tarPath);

            $phar = new \PharData($tarPath);
            $phar->extractTo($tmpPath);
            unset($phar);

            return file_get_contents("$tmpPath/run.log");
        } finally {
            if (!is_null($tmpPath) && file_exists($tmpPath)) {
                FileHelper::removeDirectory($tmpPath);
            }
        }
    }
}
