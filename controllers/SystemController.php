<?php

namespace app\controllers;

use app\resources\PublicSystemInfoResource;
use Yii;
use yii\helpers\ArrayHelper;

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
}
