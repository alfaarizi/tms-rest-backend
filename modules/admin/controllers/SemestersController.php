<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\Semester;
use app\resources\SemesterResource;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\ServerErrorHttpException;

/**
 * This class controls semester actions for admins
 */
class SemestersController extends BaseAdminRestController
{
    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return array_merge(parent::verbs(), [
            'get-next' => ['GET'],
            'add-next' => ['POST'],
        ]);
    }

    /**
     * Get the next semester
     * @return SemesterResource
     *
     * @OA\Get(
     *     path="/admin/semesters/get-next",
     *     operationId="admin::SemestersController::actionGetNext",
     *     tags={"Admin Semesters"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Common_SemesterResource_Read"),
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionGetNext()
    {
        $semester = new SemesterResource();
        $semester->name = SemesterResource::calculateNextSemesterName();
        return $semester;
    }

    /**
     * Add next semester.
     * It checks if the semester is already exists, if not it saves the new one.
     * @return SemesterResource
     * @throws ConflictHttpException
     * @throws ServerErrorHttpException
     *
     * @OA\POST(
     *     path="/admin/semesters/add-next",
     *     operationId="admin::SemestersController::actionAddNext",
     *     tags={"Admin Semesters"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(ref="#/components/parameters/yii2_fields"),
     *     @OA\Parameter(ref="#/components/parameters/yii2_expand"),
     *     @OA\Response(
     *         response=201,
     *         description="new semester added",
     *         @OA\JsonContent(ref="#/components/schemas/Common_SemesterResource_Read"),
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     *     @OA\Response(response=409, ref="#/components/responses/409"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * )
     */
    public function actionAddNext()
    {
        $semesterName = SemesterResource::calculateNextSemesterName();
        $semesterName = str_replace("-", "/", $semesterName);
        if (!is_null(Semester::findOne(['name' => $semesterName]))) {
            throw new ConflictHttpException(Yii::t('app', "Semester already exists."));
        } else {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $actualSemester = SemesterResource::findOne(['actual' => 1]);
                if ($actualSemester != null) {
                    $actualSemester->actual = 0;
                    $actualSemester->save();
                }
                $semester = new SemesterResource();
                $semester->name = $semesterName;
                $semester->actual = true;
                if ($semester->save()) {
                    $transaction->commit();

                    // Log
                    Yii::info(
                        "An admin has added a new semester ($semesterName)",
                        __METHOD__
                    );

                    $this->response->statusCode = 201;
                    return $semester;
                } else {
                    $transaction->rollBack();
                    throw new ServerErrorHttpException(Yii::t('app', "Couldn't save new semester."));
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw new ServerErrorHttpException(Yii::t('app', "Couldn't save new semester."));
            }
        }
    }
}
