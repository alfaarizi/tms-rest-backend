<?php

use yii\db\Migration;

/**
 * Stores the last Canvas sync time for groups
 */
class m220915_215651_add_group_sync_time extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%groups}}', 'lastSyncTime', $this->dateTime()->defaultValue(null)->after('synchronizerID'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%groups}}', 'lastSyncTime');
    }
}
