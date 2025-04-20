<?php

namespace app\modules\instructor\controllers;

use app\models\IpRestriction;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use Yii;
use yii\data\ArrayDataProvider;

/**
 * This class provides access to IP restrictions for instructors.
 */
class IpRestrictionController extends BaseInstructorRestController
{
    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return array_merge(
            parent::verbs(),
            [
                'index' => ['GET']
            ]
        );
    }

    /**
     *  @OA\Get(
     *     path="/instructor/ip-restriction",
     *     operationId="instructor::IpRestrictionController::actionIndex",
     *     tags={"Instructor Ip Restrictions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/IpRestriction")),
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionIndex() : ArrayDataProvider
    {
        $ipRestrictions = IpRestriction::find()->all();
        return new ArrayDataProvider([
            'allModels' => $ipRestrictions,
            'modelClass' => IpRestriction::class,
            'pagination' => false
        ]);
    }
}
