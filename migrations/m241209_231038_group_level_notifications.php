<?php

use yii\db\Migration;

/**
 * Add group level notifications
 */
class m241209_231038_group_level_notifications extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%notifications}}', 'scope', "ENUM('everyone','user','student','faculty','group') NOT NULL");
        $this->addColumn(
            '{{%notifications}}',
            'groupID',
            $this->integer()
        );
        $this->addForeignKey(
            '{{%notifications_ibfk_1}}',
            '{{%notifications}}',
            'groupID',
            '{{%groups}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('{{%notifications_ibfk_1}}', '{{%notifications}}');
        $this->dropColumn('{{%notifications}}', 'groupID');
        $this->alterColumn('{{%notifications}}', 'scope', "ENUM('everyone','user','student','faculty') NOT NULL");
    }
}
