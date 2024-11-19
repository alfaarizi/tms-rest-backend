<?php

namespace app\controllers;

use app\components\DateTimeHelpers;
use app\resources\PrivateSystemInfoResource;
use app\resources\PublicSystemInfoResource;
use app\resources\SemesterResource;
use Exception;
use Yii;
use yii\helpers\ArrayHelper;
use app\components\UnitConverterHelper;

/**
 * This class controls the system information actions
 */
class SystemController extends BaseRestController
{
    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator']['except'] = ['public-info'];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    protected function verbs(): array
    {
        return ArrayHelper::merge(
            parent::verbs(),
            [
                'public-info' => ['GET'],
                'private-info' => ['GET'],
            ]
        );
    }

    /**
     * Get public server information without authentication
     * @OA\Get (
     *     path="/common/system/public-info",
     *     tags={"Common System"},
     *     operationId="common::SystemController::actionPublicInfo",
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Common_PublicSystemInfoResource_Read"),
     *     ),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionPublicInfo(): PublicSystemInfoResource
    {
        $composerContent = json_decode(
            file_get_contents(Yii::getAlias('@app/composer.json')),
            true
        );

        $resource = new PublicSystemInfoResource();
        $resource->version = $composerContent['version'];
        return $resource;
    }

    /**
     * Get private server information
     * @OA\Get (
     *     path="/common/system/private-info",
     *     tags={"Common System"},
     *     security={{"bearerAuth":{}}},
     *     operationId="common::SystemController::actionPrivateInfo",
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Common_PrivateSystemInfoResource_Read"),
     *     ),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     * @throws Exception
     */
    public function actionPrivateInfo(): PrivateSystemInfoResource
    {
        $resource = new PrivateSystemInfoResource();
        $resource->uploadMaxFilesize = UnitConverterHelper::phpFilesizeToBytes(ini_get('upload_max_filesize'));
        $resource->postMaxSize = UnitConverterHelper::phpFilesizeToBytes(ini_get('post_max_size'));
        $resource->maxWebAppRunTime = Yii::$app->params['evaluator']['webApp']['maxWebAppRunTime'];
        $resource->isAutoTestEnabled = Yii::$app->params['evaluator']['enabled'];
        $resource->isVersionControlEnabled = Yii::$app->params['versionControl']['enabled'];
        $resource->isCanvasEnabled = Yii::$app->params['canvas']['enabled'];
        $resource->isCodeCompassEnabled = Yii::$app->params['codeCompass']['enabled'];
        $resource->userCodeFormat = Yii::$app->params['userCodeFormat'];
        $resource->serverDateTime = DateTimeHelpers::getCurrentTime();
        $resource->actualSemester = SemesterResource::findOne(['actual' => 1]);
        return $resource;
    }
}
