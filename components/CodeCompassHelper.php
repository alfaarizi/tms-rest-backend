<?php

namespace app\components;

use app\models\CodeCompassInstance;
use Docker\Docker;
use Docker\DockerClientFactory;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * A helper class for the CodeCompass integration feature.
 */
class CodeCompassHelper
{
    public static string $CACHED_IMAGE_NAME_PREFIX = 'cc-image-';

    /**
     * Checks whether the number of running containers have reached the maximum amount defined
     * in params.php
     *
     * @return bool
     */
    public static function isTooManyContainersRunning(): bool
    {
        $numContainersRunning = CodeCompassInstance::find()
            ->where(['status' => CodeCompassInstance::STATUS_RUNNING])
            ->count();

        return $numContainersRunning >= Yii::$app->params['codeCompass']['maxContainerNum'];
    }

    /**
     * Check whether a container is already running for the given Student file id
     *
     * @param int $studentFileId The id of the StudentFile
     * @return bool
     */
    public static function isContainerAlreadyRunning(int $studentFileId): bool
    {
        return CodeCompassInstance::find()
            ->where(['status' => CodeCompassInstance::STATUS_RUNNING, 'studentFileId' => $studentFileId])
            ->exists();
    }

    /**
     * Check whether there is CodeCompass instance currently starting for the given
     * StudentFile id.
     *
     * @param int $id The id of the StudentFile
     * @return bool
     */
    public static function isContainerCurrentlyStarting(int $id): bool
    {
        return CodeCompassInstance::find()
            ->where(['status' => CodeCompassInstance::STATUS_STARTING, 'studentFileId' => $id])
            ->exists();
    }

    /**
     * Selects the first port that is not used from the defined port range in params.php
     * If there is no port available returns null.
     *
     * @return string|null
     */
    public static function selectFirstAvailablePort(): ?string
    {
        $usedPorts = ArrayHelper::getColumn(CodeCompassInstance::find()->all(), 'port');
        $portRangeArray = Yii::$app->params['codeCompass']['portRange'];
        $range = range($portRangeArray[0], $portRangeArray[1]);
        return current(array_filter($range, function ($num) use ($usedPorts) {
            return !in_array($num, $usedPorts);
        })) ?: null;
    }

    /**
     * Checks whether CodeCompass feature is enabled in the params.php file.
     *
     * @return bool
     */
    public static function isCodeCompassIntegrationEnabled(): bool
    {
        return isset(Yii::$app->params['codeCompass']) ?
            Yii::$app->params['codeCompass']['enabled'] :
            false;
    }

    /**
     * Connects to the remote docker socket specified in params.php
     *
     * @return Docker The docker client
     */
    public static function createDockerClient(): Docker
    {
        return Docker::create(
            DockerClientFactory::create(
                ['remote_socket' => Yii::$app->params['codeCompass']['socket']]
            )
        );
    }

    /**
     * Deletes a cached CodeCompass image for the given taskId.
     * Does nothing if the image does not exist.
     *
     * @param int $taskId The id of the task
     * @param Docker $docker The docker client
     */
    public static function deleteCachedImageForTask(int $taskId, Docker $docker)
    {
        if (self::isImageCachedForTask($taskId, $docker)) {
            $docker->imageDelete(self::$CACHED_IMAGE_NAME_PREFIX . $taskId);
        }
    }

    /**
     * Checks whether there is a cached CodeCompass image for a given taskId
     *
     * @param int $taskId The id of the task
     * @param Docker $docker The docker client
     * @return bool
     */
    public static function isImageCachedForTask(int $taskId, Docker $docker): bool
    {
        $imageName = self::$CACHED_IMAGE_NAME_PREFIX . $taskId;
        try {
            return $docker->imageInspect($imageName) != null;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * If the task has a cachedImage, then returns its name, else returns null.
     *
     * @param int $taskId The id of the task
     * @param Docker $docker The docker client
     * @return string|null
     */
    public static function getCachedImageNameForTask(int $taskId, Docker $docker): ?string
    {
        $imageName = self::$CACHED_IMAGE_NAME_PREFIX . $taskId;
        return self::isImageCachedForTask($taskId, $docker) ? $imageName : null;
    }
}
