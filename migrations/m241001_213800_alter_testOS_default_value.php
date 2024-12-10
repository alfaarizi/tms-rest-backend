<?php

use yii\db\Migration;

/**
 * This migration sets the default value NULL to testOS
 */
class m241001_213800_alter_testOS_default_value extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%tasks}}', 'testOS', "ENUM('linux','windows') DEFAULT NULL");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('{{%tasks}}', 'testOS', "ENUM('linux','windows') NOT NULL DEFAULT 'linux'");
    }
}
