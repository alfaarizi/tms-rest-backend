<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%ip_restrictions}}`
 */
class m240520_150319_create_ip_table_for_admins extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%ip_restrictions}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'ipAddress' => $this->string(),
            'ipMask' => $this->string(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%ip_restrictions}}');
    }
}
