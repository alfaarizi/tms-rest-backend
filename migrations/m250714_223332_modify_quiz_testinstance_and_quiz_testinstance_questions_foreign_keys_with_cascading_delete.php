<?php

use yii\db\Migration;

class m250714_223332_modify_quiz_testinstance_and_quiz_testinstance_questions_foreign_keys_with_cascading_delete extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropForeignKey('{{%quiz_testinstance_questions_ibfk_2}}', '{{%quiz_testinstance_questions}}');
        $this->addForeignKey('{{%quiz_testinstance_questions_ibfk_2}}', '{{%quiz_testinstance_questions}}', 'testinstanceID', "{{%quiz_testinstances}}", 'id', 'CASCADE', 'NO ACTION');

        $this->dropForeignKey('{{%quiz_testinstances_ibfk_2}}', '{{%quiz_testinstances}}');
        $this->addForeignKey('{{%quiz_testinstances_ibfk_2}}', '{{%quiz_testinstances}}', 'testID', "{{%quiz_tests}}", 'id', 'CASCADE', 'NO ACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('{{%quiz_testinstances_ibfk_2}}', '{{%quiz_testinstances}}');
        $this->addForeignKey('{{%quiz_testinstances_ibfk_2}}', '{{%quiz_testinstances}}', 'testID', "{{%quiz_tests}}", 'id', 'NO ACTION', 'NO ACTION');

        $this->dropForeignKey('{{%quiz_testinstance_questions_ibfk_2}}', '{{%quiz_testinstance_questions}}');
        $this->addForeignKey('{{%quiz_testinstance_questions_ibfk_2}}', '{{%quiz_testinstance_questions}}', 'testinstanceID', "{{%quiz_testinstances}}", 'id', 'NO ACTION', 'NO ACTION');
    }
}
