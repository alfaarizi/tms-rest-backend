<?php

use yii\db\Migration;

/**
 * Rename the Neptun field to a more generic User Code.
 */
class m241022_045159_rename_neptun extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->renameColumn('{{%users}}', 'neptun', 'userCode');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->renameColumn('{{%users}}', 'userCode', 'neptun');
    }
}
