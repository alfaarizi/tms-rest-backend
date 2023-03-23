<?php

namespace app\components\codechecker;

use app\components\CanvasIntegration;
use app\exceptions\CodeCheckerResultNotifierException;
use app\models\CodeCheckerResult;
use app\models\StudentFile;
use yii\base\BaseObject;
use Yii;

class CodeCheckerResultNotifier extends BaseObject
{
    /**
     * Sends email and notification and saves Canvas comment based on the status of the latest CodeCheckerResult
     * belongs to the given student file.
     * @param StudentFile $studentFile
     * @return void
     * @throws CodeCheckerResultNotifierException
     */
    public function sendNotifications(StudentFile $studentFile)
    {
        if (is_null($studentFile->codeCheckerResultID)) {
            throw new CodeCheckerResultNotifierException(
                "The provided studentfile does not have a CodeCheckerResult",
                CodeCheckerResultNotifierException::CONFIG
            );
        }

        if ($studentFile->codeCheckerResult->status == CodeCheckerResult::STATUS_IN_PROGRESS) {
            throw new CodeCheckerResultNotifierException(
                "The provided studentfile does has  CodeCheckerResult in 'In Progress' stats",
                CodeCheckerResultNotifierException::CONFIG
            );
        }

        // Send email
        try {
            if (!empty($studentFile->uploader->notificationEmail)) {
                Yii::$app->mailer->compose(
                    'student/staticCodeAnalysisCompleted',
                    [
                        'studentFile' => $studentFile
                    ]
                )
                    ->setFrom(Yii::$app->params['systemEmail'])
                    ->setTo($studentFile->uploader->notificationEmail)
                    ->setSubject(Yii::t('app/mail', 'Static code analysis ready'))
                    ->send();
            }
        } catch (\Throwable $e) {
            throw new CodeCheckerResultNotifierException(
                "Failed to send email: {$e->getMessage()}",
                CodeCheckerResultNotifierException::EMAIL
            );
        }

        // Save Canvas comment
        try {
            if (Yii::$app->params['canvas']['enabled'] && !empty($studentFile->canvasID)) {
                $canvas = Yii::$container->get(CanvasIntegration::class);
                $canvas->uploadCodeCheckerResultToCanvas($studentFile);
            }
        } catch (\Throwable $e) {
            throw new CodeCheckerResultNotifierException(
                "Failed to save Canvas comment for CodeChecker result: {$e->getMessage()}",
                CodeCheckerResultNotifierException::CANVAS
            );
        }
    }
}
