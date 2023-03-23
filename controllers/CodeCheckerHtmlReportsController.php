<?php

namespace app\controllers;

use app\models\CodeCheckerResult;
use Yii;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;

class CodeCheckerHtmlReportsController extends Controller
{
    /**
     * Renders CodeChecker HTML report files
     * @param string $id ID of the CodeChecker Result
     * @param string $token Token that belongs to the result
     * @param string $fileName HTML report filename
     * @return string
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws HttpException
     */
    public function actionView(string $id, string $token, string $fileName): string
    {
        $result = CodeCheckerResult::findOne($id);

        if (is_null($result)) {
            throw new NotFoundHttpException('CodeChecker result not found');
        }

        if ($result->token !== $token) {
            throw new ForbiddenHttpException(
                Yii::t("app", "You don't have permission to view this file")
            );
        }

        $path = $result->htmlReportsDirPath . '/' . $fileName;
        if (!is_file($path)) {
            throw new NotFoundHttpException('CodeChecker Report file not found');
        }

        return $this->renderFile($path);
    }
}
