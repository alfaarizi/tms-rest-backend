<?php

namespace app\commands;

use app\components\CanvasIntegration;
use app\models\Group;
use yii\console\ExitCode;
use yii\helpers\Console;

class CanvasController extends BaseController
{
    /**
     * Runs the automatic synchronization with canvas
     * @param null $groupId Group to synchronize (empty for all)
     */
    public function actionCanvasSynchronization($groupId = null)
    {
        $groupQuery = Group::find()
            ->alias('g')
            ->joinWith('semester s')
            ->where(['IS NOT', 'canvasCourseID', null])
            ->andWhere(['actual' => true])
            ->orderBy('synchronizerID');

        if ($groupId) {
            $groupQuery = $groupQuery->andWhere(['g.id' => $groupId]);
        }

        $canvasGroups = $groupQuery->all();
        $this->stdout("Synchronizing " . count($canvasGroups) . " group(s)." . PHP_EOL);

        $synchronizer = null;
        $hasToken = false;
        foreach ($canvasGroups as $group) {
            $canvas = new CanvasIntegration();
            if ($synchronizer !== $group->synchronizerID) {
                $this->stdout("Fetching token for user {$group->synchronizer->neptun} (ID: #{$group->synchronizer->id})" . PHP_EOL);
                $hasToken = $canvas->refreshCanvasToken($group->synchronizer);
                $synchronizer = $group->synchronizerID;
            }

            if ($hasToken) {
                $this->stdout("Synchronizing group #{$group->id}" . PHP_EOL);
                $canvas->synchronizeGroupData($group);
                sleep(10);
            } else {
                $this->stderr("Failed to synchronize group #{$group->id} for user {$group->synchronizer->neptun} (ID: #{$group->synchronizer->id})" . PHP_EOL, Console::FG_RED);
            }
        }
        return ExitCode::OK;
    }
}
