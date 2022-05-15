<?php

namespace app\commands;

use app\components\CodeCompass;
use app\components\CodeCompassHelper;
use app\models\CodeCompassInstance;
use app\models\Task;
use app\models\User;
use Yii;
use yii\db\StaleObjectException;
use yii\helpers\BaseConsole;
use yii\helpers\Console;
use yii\console\ExitCode;

class CodeCompassController extends BaseController
{
    /**
     * Starts the longest WAITING CodeCompass container stored in the database.
     * Sends an email to the user that requested the CodeCompass instance.
     *
     * @return int
     * @throws StaleObjectException
     */
    public function actionStartWaitingContainer(): int
    {
        if (!CodeCompassHelper::isCodeCompassIntegrationEnabled()) {
            $this->stderr('CodeCompass integration is disabled in configuration.' . PHP_EOL, Console::FG_RED);
            return ExitCode::CONFIG;
        }

        $codeCompassInstance = CodeCompassInstance::find()->listWaitingOrderedByCreationTime()->one();
        if (is_null($codeCompassInstance)) {
            $this->stdout('There are no CodeCompass instances waiting to be started!' . PHP_EOL);
            return ExitCode::OK;
        }

        if (CodeCompassHelper::isTooManyContainersRunning()) {
            $this->stdout('There are still too many CodeCompass containers running!' . PHP_EOL);
            return ExitCode::UNAVAILABLE;
        }

        $selectedPort = CodeCompassHelper::selectFirstAvailablePort();
        if (is_null($selectedPort)) {
            $this->stdout('There is no port available to start the instance on!' . PHP_EOL);
            return ExitCode::UNAVAILABLE;
        }

        $docker = CodeCompassHelper::createDockerClient();
        $taskId = $codeCompassInstance->studentFile->taskID;

        $codeCompass = new CodeCompass(
            $codeCompassInstance->studentFile,
            CodeCompassHelper::createDockerClient(),
            $selectedPort,
            CodeCompassHelper::getCachedImageNameForTask($taskId, $docker)
        );

        $codeCompassInstance->containerId = $codeCompass->containerId;
        $codeCompassInstance->port = $selectedPort;
        $codeCompassInstance->creationTime = date('Y-m-d H:i:s');
        $codeCompassInstance->status = CodeCompassInstance::STATUS_STARTING;
        $codeCompassInstance->save(false);

        try {
            $codeCompass->start();
        } catch (\Exception $ex) {
            $codeCompassInstance->delete();
            $this->stderr('An error occurred while starting a waiting CodeCompass instance!' . PHP_EOL);
            return ExitCode::CANTCREAT;
        }

        $codeCompassInstance->errorLogs = $codeCompass->errorLogs;
        $codeCompassInstance->status = CodeCompassInstance::STATUS_RUNNING;
        $codeCompassInstance->username = $codeCompass->codeCompassUsername;
        $codeCompassInstance->password = $codeCompass->codeCompassPassword;
        $codeCompassInstance->save(false);

        // E-mail instance starter user
        $user = User::findOne($codeCompassInstance->instanceStarterUserId);
        if (!empty($user->notificationEmail)) {
            Yii::$app->mailer->compose('instructor/codeCompassStarted', [
                'instance' => $codeCompassInstance
            ])
                ->setFrom(Yii::$app->params['systemEmail'])
                ->setTo($user->notificationEmail)
                ->setSubject(Yii::t('app/mail', 'CodeCompass instance started'))
                ->send();
        }

        return ExitCode::OK;
    }

    /**
     * Stops all the CodeCompass containers that have been running for
     * longer than the containerExpireMinutes defined in params.php
     *
     * @return int
     */
    public function actionStopExpiredContainers(): int
    {
        if (!CodeCompassHelper::isCodeCompassIntegrationEnabled()) {
            $this->stderr('CodeCompass integration is disabled in configuration.' . PHP_EOL, BaseConsole::FG_RED);
            return ExitCode::CONFIG;
        }

        $expiredInstances = CodeCompassInstance::find()->listExpired()->all();
        foreach ($expiredInstances as $instance) {
            $codeCompass = new CodeCompass(
                $instance->studentFile,
                CodeCompassHelper::createDockerClient(),
                $instance->port
            );
            $codeCompass->stop();
            $instance->delete();
        }

        return 0;
    }

    /**
     * Deletes all the cached CodeCompass images store on the server,
     * except for the ones that are still running.
     *
     * @return int
     */
    public function actionClearCachedImages(): int
    {
        if (!CodeCompassHelper::isCodeCompassIntegrationEnabled()) {
            $this->stderr('CodeCompass integration is disabled in configuration.' . PHP_EOL, BaseConsole::FG_RED);
            return ExitCode::CONFIG;
        }

        $tasks = Task::find()->all();
        $docker = CodeCompassHelper::createDockerClient();

        foreach ($tasks as $task) {
            try {
                CodeCompassHelper::deleteCachedImageForTask($task->id, $docker);
            } catch (\Exception $ex) {
                $this->stderr('Could delete image for task with ID: ' . $task->id . PHP_EOL);
            }
        }

        return ExitCode::OK;
    }
}
