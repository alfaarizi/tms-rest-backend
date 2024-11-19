<?php

namespace app\commands;

use app\models\WebAppExecution;
use app\modules\instructor\components\WebAppExecutor;
use Yii;

class WebAppController extends BaseController
{
    /**
     * Shuts down expired web app containers
     * @return void
     * @throws \app\modules\instructor\components\exception\WebAppExecutionException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionShutDownExpiredExecutions()
    {
        $expiredExecutions = WebAppExecution::find()->expired()->all();

        if (empty($expiredExecutions)) {
            return;
        }

        $webAppExecutor = new WebAppExecutor();
        foreach ($expiredExecutions as $execution) {
            try {
                $webAppExecutor->stopWebApplication($execution);
                Yii::debug("Web app execution [" . $execution->id . "] shut down", __METHOD__);
            } catch (\Exception $e) {
                Yii::error(
                    "Can't terminate web app execution [" . $execution->id . "]:" . $e->getMessage() . " " . $e->getTraceAsString(),
                    __METHOD__
                );
            }
        }
    }
}
