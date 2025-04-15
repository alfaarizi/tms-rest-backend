<?php

namespace app\components\codechecker;

use app\components\CanvasIntegration;
use app\exceptions\CodeCheckerResultNotifierException;
use app\models\CodeCheckerResult;
use app\models\Submission;
use yii\base\BaseObject;
use Yii;

class CodeCheckerResultNotifier extends BaseObject
{
    /**
     * Sends email and notification and saves Canvas comment based on the status of the latest CodeCheckerResult
     * belongs to the given student file.
     * @param Submission $submission
     * @return void
     * @throws CodeCheckerResultNotifierException
     */
    public function sendNotifications(Submission $submission)
    {
        if (is_null($submission->codeCheckerResultID)) {
            throw new CodeCheckerResultNotifierException(
                "The provided submission does not have a CodeCheckerResult",
                CodeCheckerResultNotifierException::CONFIG
            );
        }

        if ($submission->codeCheckerResult->status == CodeCheckerResult::STATUS_IN_PROGRESS) {
            throw new CodeCheckerResultNotifierException(
                "The provided submission does has  CodeCheckerResult in 'In Progress' stats",
                CodeCheckerResultNotifierException::CONFIG
            );
        }

        // Send email
        try {
            if (!empty($submission->uploader->notificationEmail)) {
                Yii::$app->mailer->compose(
                    'student/staticCodeAnalysisCompleted',
                    [
                        'submission' => $submission
                    ]
                )
                    ->setFrom(Yii::$app->params['systemEmail'])
                    ->setTo($submission->uploader->notificationEmail)
                    ->setSubject(Yii::t('app/mail', 'Static code analysis complete'))
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
            if (Yii::$app->params['canvas']['enabled'] && !empty($submission->canvasID)) {
                $canvas = Yii::$container->get(CanvasIntegration::class);
                $canvas->uploadCodeCheckerResultToCanvas($submission);
            }
        } catch (\Throwable $e) {
            throw new CodeCheckerResultNotifierException(
                "Failed to save Canvas comment for CodeChecker result: {$e->getMessage()}",
                CodeCheckerResultNotifierException::CANVAS
            );
        }
    }
}
