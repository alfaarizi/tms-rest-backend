<?php

use yii\db\Migration;

/**
 * Set the synclevel to a default value for older groups, which were created before
 * the partial Canvas synchronization feature was introduced.
 */
class m241211_154841_set_canvas_synclevel_backward extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->update(
            '{{%groups}}',
            [
                'syncLevel' => 'Name lists,Tasks'
            ],
            [
                'and',
                ['not', ['canvasCourseID' => null]],
                ['syncLevel' => null]
            ]
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // This migration fixes incorrect DB entries, which can't be and shouldn't be reverted.
        return true;
    }
}
