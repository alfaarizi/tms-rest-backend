<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\Semester;
use app\resources\SemesterResource;
use yii\web\BadRequestHttpException;
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
     * Returns with the name of the next semester
     * @return SemesterResource
     */
    public function actionGetNext()
    {
        $semester = new SemesterResource();
        $semester->name = SemesterResource::calculateNextSemesterName();
        return $semester;
    }

    /**
     * Add next semester
     * It checks if the semester is already exists, if not it saves the new one.
     * @return SemesterResource
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     */
    public function actionAddNext()
    {
        $semesterName = SemesterResource::calculateNextSemesterName();
        $semesterName = str_replace("-", "/", $semesterName);
        if (!is_null(Semester::findOne(['name' => $semesterName]))) {
            throw new BadRequestHttpException(Yii::t('app', "Semester already exists."));
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
