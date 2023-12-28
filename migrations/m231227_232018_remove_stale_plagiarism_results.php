<?php

use yii\db\Migration;
use yii\db\Query;
use yii\helpers\FileHelper;

/**
 * Cleans up stale plagiarism results on the disk, which does not have a corresponding DB entry.
 */
class m231227_232018_remove_stale_plagiarism_results extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $resultRoot = Yii::getAlias("@appdata/plagiarism/plagiarism-result");
        if (!file_exists($resultRoot)) {
            return true; // no plagiarism results => no stale results to clean up
        }

        $resultDirs = FileHelper::findDirectories($resultRoot, ['recursive' => false]);
        $resultIds = array_map(fn($path): string => basename($path), $resultDirs);
        if (empty($resultDirs)) {
            return true; // no plagiarism results => no stale results to clean up
        }

        $validIds = (new Query())
            ->select(['id'])
            ->from('{{%plagiarisms}}')
            ->column();

        $deleteIds = array_diff($resultIds, $validIds);
        foreach ($deleteIds as $id) {
            FileHelper::removeDirectory("$resultRoot/$id");
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // This migration removes stale plagiarism check results from the disk, which can't be and shouldn't be reverted.
        return true;
    }
}
