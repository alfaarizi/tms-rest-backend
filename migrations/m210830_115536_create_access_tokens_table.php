<?php

use yii\db\Migration;

/**
 * Create a table storing access
 */
class m210830_115536_create_access_tokens_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%access_tokens}}', [
            'token' => $this->string(),
            'imageToken' => $this->string(),
            'userId' => $this->integer()->notNull(),
            'validUntil' => $this->dateTime()->notNull()
        ]);
        $this->addPrimaryKey('token', '{{%access_tokens}}', 'token');

        $this->addForeignKey(
            '{{%accessTokens_ibfk_1}}',
            '{{%access_tokens}}',
            'userId',
            '{{%users}}',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%access_tokens}}');
    }
}
