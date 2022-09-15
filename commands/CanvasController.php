<?php

namespace app\commands;

use app\components\CanvasIntegration;
use app\models\Group;
use app\models\queries\GroupQuery;
use yii\console\ExitCode;
use yii\helpers\Console;

class CanvasController extends BaseController
{
    /**
     * Runs the automatic synchronization with Canvas
     * @param null $groupId Group to synchronize (empty for all)
     */
    public function actionSynchronize($groupId = null)
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

        $this->synchronize($groupQuery);
        return ExitCode::OK;
    }

    /**
     * Runs the automatic synchronization with Canvas, prioritizing groups not synchronized for the longest time
     * @param null $count Number of groups to synchronize (zero for all)
     */
    public function actionSynchronizePrioritized($count = 1)
    {
        $groupQuery = Group::find()
            ->alias('g')
            ->joinWith('semester s')
            ->where(['IS NOT', 'canvasCourseID', null])
            ->andWhere(['actual' => true])
            ->orderBy('lastSyncTime');

        if ($count > 0) {
            $groupQuery = $groupQuery->limit($count);
        }

        $this->synchronize($groupQuery);
        return ExitCode::OK;
    }

    /**
     * Performs the synchronization
     * @param GroupQuery $groupQuery
     */
    private function synchronize(GroupQuery $groupQuery)
    {
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
    }
}
