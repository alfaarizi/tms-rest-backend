<?php

namespace app\commands;

use app\components\plagiarism\MossPlagiarismFinder;
use app\models\Plagiarism;
use Yii;
use yii\console\ExitCode;
use yii\helpers\Console;

class PlagiarismController extends BaseController
{
    /**
     * Send the given plagiarism check to the Moss service
     * @param int $id The specified plagiarism check.
     * @return int
     */
    public function actionRunMoss(int $id)
    {
        if (Yii::$app->params['mossId'] === '') {
            $this->stderr(
                'Moss is disabled. Contact the administrator for more information.' . PHP_EOL,
                Console::FG_RED
            );
            return ExitCode::CONFIG;
        }

        if (is_null(Plagiarism::findOne($id))) {
            $this->stderr('The specified plagiarism check does not exist.' . PHP_EOL, Console::FG_RED);
            return ExitCode::NOINPUT;
        }

        // This may take some time.
        set_time_limit(1800);
        ini_set('default_socket_timeout', 900);

        (new MossPlagiarismFinder($id))->start();

        $plagiarism = Plagiarism::findOne($id);
        $this->stdout('The specified plagiarism check was successfully performed.' . PHP_EOL, Console::FG_GREEN);
        $this->stdout('URL: ' . $plagiarism->response . PHP_EOL);
        return ExitCode::OK;
    }
}
