<?php

use yii\db\Migration;

/**
 * Class m210917_133443_add_timezone_to_groups
 */
class m210917_133443_add_timezone_to_groups extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%groups}}', 'timezone', $this->string());
        // Fill the new column with the default timezone
        $this->update('{{%groups}}', ['timezone' => Yii::$app->timeZone], ['timezone' => null]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%groups}}', 'timezone');
    }
}
