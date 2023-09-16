<?php

use yii\db\Migration;

/**
 * Create table for storing test case results.
 */
class m230915_094620_create_testcase_results extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%test_results}}', [
            'id' => $this->primaryKey(),
            'testCaseID' => $this->integer()->notNull(),
            'studentFileID' => $this->integer()->notNull(),
            'isPassed' => $this->boolean()->notNull(),
            'errorMsg' => $this->text(),
        ]);

        $this->addForeignKey(
            '{{%testResults_ibfk_1}}',
            '{{%test_results}}',
            'testCaseID',
            '{{%test_cases}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            '{{%testResults_ibfk_2}}',
            '{{%test_results}}',
            'studentFileID',
            '{{%student_files}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('{{%testResults_ibfk_1}}', '{{%test_results}}');
        $this->dropForeignKey('{{%testResults_ibfk_2}}', '{{%test_results}}');
        $this->dropTable('{{%test_results}}');
    }
}
