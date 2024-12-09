<?php

use yii\db\Migration;

/**
 * Rename field 'dismissable' to 'dismissible' in the 'notifications' table.
 */
class m241209_205524_rename_notification_dismissible extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->renameColumn('{{%notifications}}', 'dismissable', 'dismissible');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->renameColumn('{{%notifications}}', 'dismissible', 'dismissable');
    }
}
