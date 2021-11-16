<?php

use yii\db\Migration;

/**
 * Class m210927_225341_add_custom_email
 */
class m210927_225341_add_custom_email extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->addColumn('{{%users}}', 'customEmail', $this->string(50)->defaultValue(null));
        $this->addColumn('{{%users}}', 'customEmailConfirmed', $this->boolean()->notNull()->defaultValue(false));
        $this->addColumn('{{%users}}', 'customEmailConfirmationCode', $this->string(32)->defaultValue(null));
        $this->addColumn('{{%users}}', 'customEmailConfirmationExpiry', $this->dateTime()->defaultValue(null));
        $this->addColumn('{{%users}}', 'notificationTarget', "enum('official','custom','none') NOT NULL DEFAULT 'official'");
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->dropColumn('{{%users}}', 'customEmail');
        $this->dropColumn('{{%users}}', 'customEmailConfirmed');
        $this->dropColumn('{{%users}}', 'customEmailConfirmationCode');
        $this->dropColumn('{{%users}}', 'customEmailConfirmationExpiry');
        $this->dropColumn('{{%users}}', 'notificationTarget');
    }
}
