<?php

use yii\db\Migration;

/**
 * Create examination tables.
 */
class m210830_085912_init_examination_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        }

        // exam_questionsets
        $this->createTable(
            '{{%exam_questionsets}}',
            [
                'id' => $this->primaryKey(),
                'name' => $this->string(45)->notNull(),
                'courseID' => $this->integer()->notNull(),
            ],
            $tableOptions
        );
        $this->addForeignKey(
            '{{%questionsets_ibfk}}',
            '{{%exam_questionsets}}',
            ['courseID'],
            '{{%courses}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );

        // exam_questions
        $this->createTable(
            '{{%exam_questions}}',
            [
                'id' => $this->primaryKey(),
                'text' => $this->string(2500)->notNull(),
                'questionsetID' => $this->integer()->notNull(),
            ],
            $tableOptions
        );
        $this->addForeignKey(
            '{{%questions_ibfk}}',
            '{{%exam_questions}}',
            ['questionsetID'],
            '{{%exam_questionsets}}',
            ['id'],
            'CASCADE',
            'NO ACTION'
        );

        // exam_answers
        $this->createTable(
            '{{%exam_answers}}',
            [
                'id' => $this->primaryKey(),
                'text' => $this->string(2500)->notNull(),
                'correct' => $this->boolean()->notNull(),
                'questionID' => $this->integer()->notNull(),
            ],
            $tableOptions
        );

        $this->addForeignKey(
            '{{%answers_ibfk}}',
            '{{%exam_answers}}',
            ['questionID'],
            '{{%exam_questions}}',
            ['id'],
            'CASCADE',
            'NO ACTION'
        );

        // exam_tests
        $this->createTable(
            '{{%exam_tests}}',
            [
                'id' => $this->primaryKey(),
                'name' => $this->string(45)->notNull(),
                'questionamount' => $this->integer()->notNull()->defaultValue('0'),
                'duration' => $this->integer()->notNull()->defaultValue('0'),
                'shuffled' => $this->boolean()->defaultValue('0'),
                'unique' => $this->boolean()->defaultValue('0'),
                'availablefrom' => $this->dateTime()->notNull(),
                'availableuntil' => $this->dateTime()->notNull(),
                'groupID' => $this->integer()->notNull(),
                'questionsetID' => $this->integer()->notNull(),
            ],
            $tableOptions
        );
        $this->createIndex('{{%tests_ibfk_1}}', '{{%exam_tests}}', ['questionsetID']);
        $this->createIndex('{{%tests_ibfk_2}}', '{{%exam_tests}}', ['groupID']);
        $this->addForeignKey(
            '{{%exam_tests_ibfk_1}}',
            '{{%exam_tests}}',
            ['questionsetID'],
            '{{%exam_questionsets}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            '{{%exam_tests_ibfk_2}}',
            '{{%exam_tests}}',
            ['groupID'],
            '{{%groups}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );

        // exam_testinstances
        $this->createTable(
            '{{%exam_testinstances}}',
            [
                'id' => $this->primaryKey(),
                'starttime' => $this->dateTime(),
                'finishtime' => $this->dateTime(),
                'submitted' => $this->boolean()->defaultValue('0'),
                'score' => $this->integer()->defaultValue('0'),
                'userID' => $this->integer()->notNull(),
                'testID' => $this->integer()->notNull(),
            ],
            $tableOptions
        );

        $this->createIndex('{{%testinstances_ibfk_1}}', '{{%exam_testinstances}}', ['userID']);
        $this->createIndex('{{%testinstances_ibfk_2}}', '{{%exam_testinstances}}', ['testID']);

        $this->addForeignKey(
            '{{%exam_testinstances_ibfk_1}}',
            '{{%exam_testinstances}}',
            ['userID'],
            '{{%users}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            '{{%exam_testinstances_ibfk_2}}',
            '{{%exam_testinstances}}',
            ['testID'],
            '{{%exam_tests}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );

        // exam_testinstance_questions
        $this->createTable(
            '{{%exam_testinstance_questions}}',
            [
                'testinstanceID' => $this->integer()->notNull(),
                'questionID' => $this->integer()->notNull(),
            ],
            $tableOptions
        );
        $this->createIndex('{{%testinstancequestions_ibfk_1}}', '{{%exam_testinstance_questions}}', ['questionID']);
        $this->createIndex(
            'testinstancequestions_unique',
            '{{%exam_testinstance_questions}}',
            ['testinstanceID', 'questionID'],
            true
        );
        $this->addForeignKey(
            '{{%exam_testinstance_questions_ibfk_1}}',
            '{{%exam_testinstance_questions}}',
            ['questionID'],
            '{{%exam_questions}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            '{{%exam_testinstance_questions_ibfk_2}}',
            '{{%exam_testinstance_questions}}',
            ['testinstanceID'],
            '{{%exam_testinstances}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );

        // exam_answers_submitted
        $this->createTable(
            '{{%exam_answers_submitted}}',
            [
                'testinstanceID' => $this->integer()->notNull(),
                'answerID' => $this->integer(),
            ],
            $tableOptions
        );
        $this->createIndex('{{%submittedanswers_ibfk_2}}', '{{%exam_answers_submitted}}', ['answerID']);
        $this->createIndex(
            'submittedanswers_unique',
            '{{%exam_answers_submitted}}',
            ['testinstanceID', 'answerID'],
            true
        );
        $this->addForeignKey(
            '{{%exam_answers_submitted_ibfk_1}}',
            '{{%exam_answers_submitted}}',
            ['testinstanceID'],
            '{{%exam_testinstances}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            '{{%exam_answers_submitted_ibfk_2}}',
            '{{%exam_answers_submitted}}',
            ['answerID'],
            '{{%exam_answers}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%exam_answers_submitted}}');
        $this->dropTable('{{%exam_testinstance_questions}}');
        $this->dropTable('{{%exam_testinstances}}');
        $this->dropTable('{{%exam_tests}}');
        $this->dropTable('{{%exam_answers}}');
        $this->dropTable('{{%exam_questions}}');
        $this->dropTable('{{%exam_questionsets}}');
    }
}
