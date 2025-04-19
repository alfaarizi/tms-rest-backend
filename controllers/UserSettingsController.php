<?php

namespace app\controllers;

use app\models\User;
use app\resources\UserExtendedResource;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;

/**
 * This class controls the user settings-related actions.
 */
class UserSettingsController extends BaseRestController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator']['optional'] = ['confirm-email'];
        return $behaviors;
    }

    protected function verbs()
    {
        return ArrayHelper::merge(
            parent::verbs(),
            [
                'index' => ['GET'],
                'update' => ['PUT'],
                'confirm-email' => ['POST'],
            ]
        );
    }

    /**
     * Get settings of the current user
     * @OA\Get(
     *     path="/common/user-settings",
     *     operationId="common::UserSettingsController::actionIndex",
     *     tags={"Common UserSettings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Common_UserExtendedResource_Read"),
     *     ),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionIndex(): UserExtendedResource
    {
        return $this->findResource();
    }

    /**
     * Change settings of the current user
     * @return UserExtendedResource|array A UserExtendedResource object upon success, validation errors upon failure.
     * @throws BadRequestHttpException
     * @throws \yii\base\InvalidConfigException
     *
     * @OA\Put(
     *     path="/common/user-settings",
     *     operationId="common::UserSettingsController::actionUpdate",
     *     tags={"Common UserSettings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\RequestBody(
     *         description="updated settings",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(ref="#/components/schemas/Common_UserExtendedResource_ScenarioSettings"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="settings updated",
     *         @OA\JsonContent(ref="#/components/schemas/Common_UserExtendedResource_Read"),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=422, ref="#/components/responses/422"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionUpdate()
    {
        $user = $this->findResource();
        if (!$user->load(Yii::$app->getRequest()->getBodyParams(), '')) {
            throw new BadRequestHttpException('No parameters provided');
        }
        if ($user->validate()) {
            $confirmationCode = $user->getConfirmationCodeIfNecessary();
            $user->save();
            if ($confirmationCode) {
                // The user may have changed the app language and set a new custom address
                // at the same time; use the new language in the confirmation email
                Yii::$app->language = $user->locale;
                Yii::$app->mailer->compose('site/confirmCustomEmail', [
                    'user' => $user,
                    'url' => Yii::$app->params['frontendUrl'] . '/confirm-email/' . $confirmationCode,
                    'confirmationCode' => $confirmationCode,
                ])
                    ->setFrom(Yii::$app->params['systemEmail'])
                    ->setTo($user->customEmail)
                    ->setSubject(Yii::t('app/mail', 'Please confirm your custom email address'))
                    ->send();
            }
            return $user;
        } else {
            $this->response->statusCode = 422;
            return $user->errors;
        }
    }

    /**
     * Handle custom email address confirmation requests
     * @param string $code The email confirmation code.
     * @return array The `currentUser` key describes whether the confirmed user equals the current user.
     * @throws BadRequestHttpException
     *
     * @OA\Post(
     *     path="/common/user-settings/confirm-email",
     *     operationId="common::UserSettingsController::actionConfirmEmail",
     *     tags={"Common UserSettings"},
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="The email confirmation code",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="settings updated",
     *         @OA\JsonContent(
     *             @OA\Property(type="boolean", property="currentUser"),
     *         ),
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionConfirmEmail(string $code): array
    {
        $user = User::findByConfirmationCode($code);
        if ($user instanceof User) {
            $user->customEmailConfirmed = true;
            $user->save();
            return ['currentUser' => $user->id === Yii::$app->user->id];
        } else {
            throw new BadRequestHttpException(Yii::t(
                'app',
                'The email confirmation failed. Either you provided a wrong confirmation code, or the code has expired.'
            ));
        }
    }

    /**
     * Get the UserExtendedResource object of the current user.
     */
    private function findResource(): UserExtendedResource
    {
        $user = UserExtendedResource::findOne(Yii::$app->user->id);
        $user->scenario = User::SCENARIO_SETTINGS;
        return $user;
    }
}
