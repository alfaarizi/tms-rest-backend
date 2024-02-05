<?php

use yii\db\Migration;

/**
 * Create table for storing notifications.
 */
class m231005_175047_create_notifications_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%notifications}}', [
            'id' => $this->primaryKey(),
            'message' => $this->text()->notNull(),
            'startTime' => $this->dateTime()->notNull(),
            'endTime' => $this->dateTime()->notNull(),
            'isAvailableForAll' => $this->boolean()->notNull()->defaultValue(false),
            'dismissable' => $this->boolean()->notNull()->defaultValue(true),
        ]);

        $this->createTable('{{%notification_users}}', [
            'notificationID' => $this->integer()->notNull(),
            'userID' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            '{{%notification_users_ibfk_1}}',
            '{{%notification_users}}',
            ['notificationID'],
            '{{%notifications}}',
            ['id'],
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            '{{%notification_users_ibfk_2}}',
            '{{%notification_users}}',
            ['userID'],
            '{{%users}}',
            ['id'],
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%notification_users}}');
        $this->dropTable('{{%notifications}}');
    }
}
