<?php

use yii\db\Migration;

/**
 * Rename the type field to activity in the ip_addresses table.
 * Also enforce the plural naming convention on the table name and the capitalized naming convention on the ID field.
 */
class m241213_115321_rename_ipaddress_activity extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->renameColumn('{{%ip_address}}', 'type', 'activity');
        $this->renameColumn('{{%ip_address}}', 'submissionId', 'submissionID');
        $this->renameTable('{{%ip_address}}', '{{%ip_addresses}}');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->renameTable('{{%ip_addresses}}', '{{%ip_address}}');
        $this->renameColumn('{{%ip_address}}', 'submissionID', 'submissionId');
        $this->renameColumn('{{%ip_address}}', 'activity', 'type');
    }
}
