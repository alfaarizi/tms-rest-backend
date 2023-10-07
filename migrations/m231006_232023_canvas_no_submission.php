<?php

require_once('m230923_235005_canvas_no_submission.php');

use yii\db\Migration;

/**
 * 'No submission' records were not created for new Canvas tasks created after applying
 * m230923_235005_canvas_no_submission. This migration populates the missing records,
 * by reapplying the same migration.
 */
class m231006_232023_canvas_no_submission extends \m230923_235005_canvas_no_submission
{
    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Migration m230923_235005_canvas_no_submission contains the revert method.
        return true;
    }
}
