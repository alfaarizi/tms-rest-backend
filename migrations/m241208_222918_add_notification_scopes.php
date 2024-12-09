<?php

use yii\db\Migration;

/**
 * Adds notification scopes.
 */
class m241208_222918_add_notification_scopes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // add scope column
        $this->addColumn('{{%notifications}}', 'scope', "ENUM('everyone','user','student','faculty') NOT NULL AFTER `isAvailableForAll`");

        // Update scope column to 'everyone' where isAvailableForAll is true
        $this->update('{{%notifications}}', ['scope' => 'everyone'], ['isAvailableForAll' => true]);

        // Update scope column to 'user' where isAvailableForAll is false
        $this->update('{{%notifications}}', ['scope' => 'user'], ['isAvailableForAll' => false]);

        // remove isAvailableForAll column
        $this->dropColumn('{{%notifications}}', 'isAvailableForAll');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // add isAvailableForAll column
        $this->addColumn('{{%notifications}}', 'isAvailableForAll', $this->boolean()->notNull()->defaultValue(false)->after('scope'));

        // Update isAvailableForAll column to true where scope is 'everyone'
        $this->update('{{%notifications}}', ['isAvailableForAll' => true], ['scope' => 'everyone']);

        // Update isAvailableForAll column to false where scope is anything other than 'everyone'
        $this->update('{{%notifications}}', ['isAvailableForAll' => false], ['<>', 'scope', 'everyone']);

        // remove scope column
        $this->dropColumn('{{%notifications}}', 'scope');
    }
}
