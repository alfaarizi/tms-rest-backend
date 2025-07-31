<?php

use yii\db\Migration;

/**
 * Adds “Task unlocked” and “Verify” ENUMS to the ip_addresses.activity ENUM
 * so that entering or exiting a task password is logged in the IpAddress table.
 */
class m250309_225513_add_new_enums_to_ip_address extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Modify ENUM column to include new activity types
        $this->alterColumn('{{%ip_addresses}}', 'activity', "ENUM('Login', 'Submission upload', 'Submission download', 'Task unlock', 'Task verify')");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Revert ENUM column to previous state
        $this->alterColumn('{{%ip_addresses}}', 'activity', "ENUM('Login', 'Submission upload', 'Submission download')");
    }
}
