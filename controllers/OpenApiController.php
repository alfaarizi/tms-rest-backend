<?php

namespace app\controllers;

use app\components\openapi\ConstantHelpers;
use app\components\openapi\SchemaGenerator;
use light\swagger\SwaggerAction;
use light\swagger\SwaggerApiAction;
use Yii;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\Controller;

/**
 *  Integrates the light/yii2-swagger composer package to generate OpenAPI docs and render SwaggerUI
 */
class OpenApiController extends Controller
{
    /**
     * @param \yii\base\Action $action
     * @return bool
     * @throws BadRequestHttpException
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Swagger UI interface only enabled in development environment
        /** @phpstan-ignore-next-line booleanNot.alwaysTrue (YII_ENV_DEV can be either true or false) */
        if (!YII_ENV_DEV) {
            throw new BadRequestHttpException(Yii::t('app', 'This action is not allowed in the current environment!'));
        }

        /** @phpstan-ignore-next-line deadCode.unreachable (previous if construct won't always execute) */
        if ($action->id === 'json') {
            // Set constants
            ConstantHelpers::setApiInfo();
            ConstantHelpers::setServerInfo();

            // Generate schemas for model and resource classes
            $schemaGenerator = Yii::$app->swagger;
            $schemaGenerator->clearOutputDir();
            $schemaGenerator->generateSchemas();
        }

        return true;
    }

    /**
     * @return array[]
     */
    public function actions()
    {
        return [
            //The document preview address: basepath/common/open-api/doc
            'swagger-ui' => [
                'class' => SwaggerAction::class,
                'restUrl' => Url::to(['/common/open-api/json'], true),
            ],
            //The resultUrl action: basepath/common/open-api/json
            'json' => [
                'class' => SwaggerApiAction::class,
                //The scan directories, you should use real path there.
                'scanDir' => array_map(function ($path) {
                    return Yii::getAlias($path);
                }, ConstantHelpers::SCAN_DIRS),
                // Cache output
                'cache' => 'cache',
            ],
        ];
    }
}
