<?php

namespace app\components\openapi;

use Yii;

/**
 * Contains helper methods to set constant values used in OpenAPI annotations
 */
class ConstantHelpers
{
    // Scanned directories in the Yii2 path alias format
    // light/yii2-swagger and zircote/swagger-php configuration
    public const SCAN_DIRS = [
        '@app/runtime/openapi-schemas/',
        '@app/components/openapi/definitions',
        '@app/controllers',
        '@app/modules/student/controllers',
        '@app/modules/instructor/controllers',
        '@app/modules/admin/controllers',
    ];

    /**
     * Reads api info from composer.json
     * @return void
     */
    public static function setApiInfo(): void
    {
        $composerContent = file_get_contents(Yii::getAlias('@app/composer.json'));
        $composerContent = json_decode($composerContent, true);
        define('OPEN_API_NAME', $composerContent['name']);
        define('OPEN_API_DESCRIPTION', $composerContent['description']);
        define('OPEN_API_VERSION', $composerContent['version']);
    }

    /**
     * Set host based on the current env
     * @return void
     * @throws \yii\base\InvalidConfigException
     */
    public static function setServerInfo(): void
    {
        if (Yii::$app instanceof \yii\console\Application) {
            define('OPEN_API_HOST', Yii::$app->params['backendUrl']);
        } else {
            define('OPEN_API_HOST', Yii::$app->getUrlManager()->getBaseUrl());
        }
    }
}
