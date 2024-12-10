<?php

use yii\db\Migration;

/**
 * Add field for partial canvas sync for groups.
 */
class m240922_180918_canvas_partial_sync extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%groups}}',
            'syncLevel',
            "SET('Name lists','Tasks')"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%groups}}', 'syncLevel');
    }
}
