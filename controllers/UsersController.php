<?php

namespace app\controllers;

use app\resources\UserResource;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;

class UsersController extends BaseRestController
{
    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['admin', 'faculty'],
                ],
            ]
        ];

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
                'student' => ['GET'],
                'faculty' => ['GET'],
            ]
        );
    }

    /**
     * Search for users by a substring
     * @param string $text The substring
     * @return ArrayDataProvider List of users that contain the substring either in their names or user codes
     *         and that have student role
     * @throws BadRequestHttpException
     * @OA\Get(
     *     path="/common/users/student",
     *     operationId="common::UsersController::actionStudent",
     *     tags={"Common UsersStudent"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *          name="text",
     *          in="query",
     *          description="The substring",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *     @OA\Response(
     *         response=200,
     *         description="search completed",
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionStudent(string $text): ArrayDataProvider
    {
        $users = UserResource::search($text);
        $users = array_filter($users, fn (UserResource $user) => $user->isStudent);
        return new ArrayDataProvider(
            [
                'allModels' => $users,
                'modelClass' => UserResource::class,
                'pagination' => false
            ]
        );
    }

    /**
     * Search for users by a substring
     * @param string $text The substring
     * @return ArrayDataProvider List of users that contain the substring either in their names or user codes
     *         and that have faculty role
     * @throws BadRequestHttpException
     * @OA\Get(
     *     path="/common/users/faculty",
     *     operationId="common::UsersController::actionFaculty",
     *     tags={"Common UsersFaculty"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *          name="text",
     *          in="query",
     *          description="The substring",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *     @OA\Response(
     *         response=200,
     *         description="search completed",
     *     ),
     *    @OA\Response(response=400, ref="#/components/responses/400"),
     *    @OA\Response(response=401, ref="#/components/responses/401"),
     *    @OA\Response(response=403, ref="#/components/responses/403"),
     *    @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionFaculty(string $text): ArrayDataProvider
    {
        $users = UserResource::search($text);
        $users = array_filter($users, fn (UserResource $user) => $user->isFaculty);
        return new ArrayDataProvider(
            [
                'allModels' => $users,
                'modelClass' => UserResource::class,
                'pagination' => false
            ]
        );
    }
}
