<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%structural_requirements}}`.
 */
class m250216_184829_create_structural_requirements_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%structural_requirements}}', [
            'id' => $this->primaryKey(),
            'taskID' => $this->integer()->notNull(),
            'regexExpression' => $this->string()->notNull(),
            'type' => "ENUM('Includes', 'Excludes') NOT NULL",
        ]);

        $this->addForeignKey(
            '{{%structural_requirements_ibfk_1}}',
            '{{%structural_requirements}}',
            'taskID',
            '{{%tasks}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%structural_requirements}}');
    }
}
