<?php

use yii\db\Migration;

/**
 * Class m220610_125357_subscription_notes
 */
class m220610_125357_subscription_notes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%subscriptions}}', 'notes', $this->string(500)->defaultValue(''));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%subscriptions}}', 'notes');
    }
}
