<?php

namespace app\commands;

use app\components\plagiarism\AbstractPlagiarismFinder;
use app\modules\instructor\resources\PlagiarismResource;
use Yii;
use yii\console\ExitCode;
use yii\helpers\Console;

class PlagiarismController extends BaseController
{
    /**
     * Run the given plagiarism check.
     * @param int $id The specified plagiarism check.
     */
    public function actionRun(int $id): int
    {
        $plagiarism = PlagiarismResource::findOne($id);
        if ($plagiarism === null) {
            $this->stderr('The specified plagiarism check does not exist.' . PHP_EOL, Console::FG_RED);
            return ExitCode::NOINPUT;
        }

        $finder = Yii::$container->get(AbstractPlagiarismFinder::class, [$plagiarism]);
        if (!$finder::isEnabled()) {
            $this->stderr(
                "The requested plagiarism type ({$plagiarism->type}) has been disabled since creating the request." . PHP_EOL,
                Console::FG_RED
            );
            return ExitCode::CONFIG;
        }

        // This may take some time.
        ini_set('default_socket_timeout', 900);

        $finder->start();

        $this->stdout('The specified plagiarism check was successfully performed.' . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }
}
