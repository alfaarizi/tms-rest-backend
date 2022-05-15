<?php

namespace app\commands;

use app\models\AccessToken;
use yii\console\ExitCode;
use Yii;

class SystemController extends BaseController
{
    /**
     * Deletes expired access tokens from the database
     * @return int
     */
    public function actionClearExpiredAccessTokens()
    {
        $count = AccessToken::deleteAll('validUntil < NOW()');

        // Log
        Yii::info(
            "Successfully deleted $count expired access tokens from database",
            __METHOD__
        );

        return ExitCode::OK;
    }
}
