<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Resets static analysis status for submissions where newer upload is available.
 *
 * MR !160 accidentally removed resetting the status of the static analysis upon new, subsequent uploads.
 * The bug introduced data inconsistency in the DB, as older static analysis reports are linked to the submissions.
 * This migration fixes the issue by resetting the status where the upload time is greater than the static analysis
 * report creation time. Static analysis will be performed for the newest versions of those submissions.
 */
class m241110_190917_reset_outdated_sa extends Migration
{
    private const BATCH_SIZE = 1000;

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Query corrupted submissions
        $query = (new Query())
            ->select(['{{%submissions}}.id'])
            ->from('{{%submissions}}')
            ->innerJoin('{{%codechecker_results}}', '{{%codechecker_results}}.id = {{%submissions}}.codeCheckerResultID')
            ->where('{{%codechecker_results}}.`createdAt` < {{%submissions}}.`uploadTime`');

        // Count them
        $count = $query->count();

        for ($i = 0; $i <= $count; $i += self::BATCH_SIZE) {
            // Get the current batch
            $ids = $query
                ->offset($i)
                ->limit(self::BATCH_SIZE)
                ->column();

            foreach ($ids as $id) {
                $this->update('{{%submissions}}', ['codeCheckerResultID' => null], ['id' => $id]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // This migration resets static analysis for submissions, where it was not performed for subsequent uploads
        // due to a bug in the codebase. This can't be and shouldn't be reverted.
        return true;
    }
}
