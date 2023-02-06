<?php

use yii\db\Migration;

/**
 * Remove all CanvasIntegration::saveSolution log entries, as they became highly duplicated.
 */
class m230205_221140_remove_canvas_submission_logs extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->delete('{{%log}}', [
            'category' => 'app\components\CanvasIntegration::saveSolution',
            'level' => 4,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230205_221140_remove_canvas_submission_logs cannot be reverted.\n" .
        "Reason: deleted log entries cannot be recovered anymore!\n";

        return false;
    }
}
