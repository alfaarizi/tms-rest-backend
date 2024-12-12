<?php

use yii\db\Migration;

/**
 * Extends the 'ip_address' table with further details.
 *
 * The type of the entry ('type') and a timestamp ('logTime') is added.
 */
class m241016_122456_extensive_ip_logging extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%ip_address}}', 'type', "ENUM('Login', 'Submission upload', 'Submission download')");
        $this->update('{{%ip_address}}', ['type' => 'Submission upload']);
        $this->addColumn('{{%ip_address}}', 'logTime', $this->timestamp()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%ip_address}}', 'type');
        $this->dropColumn('{{%ip_address}}', 'logTime');
    }
}
