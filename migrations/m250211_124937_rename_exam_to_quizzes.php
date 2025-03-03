<?php

use yii\db\Migration;

/**
 * Class m250211_124937_rename_exam_to_quizzes
 */
class m250211_124937_rename_exam_to_quizzes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        //quiz_questionsets
        $this->dropForeignKey('{{%questionsets_ibfk}}', '{{%exam_questionsets}}');
        $this->renameTable('{{%exam_questionsets}}', '{{%quiz_questionsets}}');
        $this->addForeignKey(
            '{{%quiz_questionsets_ibfk}}',
            '{{%quiz_questionsets}}',
            ['courseID'],
            '{{%courses}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );

        //quiz_questions
        $this->dropForeignKey('{{%questions_ibfk}}', '{{%exam_questions}}');
        $this->renameTable('{{%exam_questions}}', '{{%quiz_questions}}');
        $this->addForeignKey(
            '{{%quiz_questions_ibfk}}',
            '{{%quiz_questions}}',
            ['questionsetID'],
            '{{%quiz_questionsets}}',
            ['id'],
            'CASCADE',
            'NO ACTION'
        );

        //quiz_answers
        $this->dropForeignKey('{{%answers_ibfk}}', '{{%exam_answers}}');
        $this->renameTable('{{%exam_answers}}', '{{%quiz_answers}}');
        $this->addForeignKey(
            '{{%quiz_answers_ibfk}}',
            '{{%quiz_answers}}',
            ['questionID'],
            '{{%quiz_questions}}',
            ['id'],
            'CASCADE',
            'NO ACTION'
        );

        //quiz_tests
        $this->dropForeignKey('{{%exam_tests_ibfk_1}}', '{{%exam_tests}}');
        $this->dropForeignKey('{{%exam_tests_ibfk_2}}', '{{%exam_tests}}');
        $this->dropIndex('{{%tests_ibfk_1}}', '{{%exam_tests}}');
        $this->dropIndex('{{%tests_ibfk_2}}', '{{%exam_tests}}');
        $this->renameTable('{{%exam_tests}}', '{{%quiz_tests}}');
        $this->createIndex('questionsetID', '{{%quiz_tests}}', ['questionsetID']);
        $this->createIndex('groupID', '{{%quiz_tests}}', ['groupID']);
        $this->addForeignKey(
            '{{%quiz_tests_ibfk_1}}',
            '{{%quiz_tests}}',
            ['questionsetID'],
            '{{%quiz_questionsets}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            '{{%quiz_tests_ibfk_2}}',
            '{{%quiz_tests}}',
            ['groupID'],
            '{{%groups}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );

        //quiz_testinstances
        $this->dropForeignKey('{{%exam_testinstances_ibfk_1}}', '{{%exam_testinstances}}');
        $this->dropForeignKey('{{%exam_testinstances_ibfk_2}}', '{{%exam_testinstances}}');
        $this->dropIndex('{{%testinstances_ibfk_1}}', '{{%exam_testinstances}}');
        $this->dropIndex('{{%testinstances_ibfk_2}}', '{{%exam_testinstances}}');
        $this->renameTable('{{%exam_testinstances}}', '{{%quiz_testinstances}}');
        $this->createIndex('userID', '{{%quiz_testinstances}}', ['userID']);
        $this->createIndex('testID', '{{%quiz_testinstances}}', ['testID']);
        $this->addForeignKey(
            '{{%quiz_testinstances_ibfk_1}}',
            '{{%quiz_testinstances}}',
            ['userID'],
            '{{%users}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            '{{%quiz_testinstances_ibfk_2}}',
            '{{%quiz_testinstances}}',
            ['testID'],
            '{{%quiz_tests}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );

        //quiz_testinstance_questions
        $this->dropForeignKey('{{%exam_testinstance_questions_ibfk_1}}', '{{%exam_testinstance_questions}}');
        $this->dropForeignKey('{{%exam_testinstance_questions_ibfk_2}}', '{{%exam_testinstance_questions}}');
        $this->dropIndex('{{%testinstancequestions_ibfk_1}}', '{{%exam_testinstance_questions}}');
        $this->dropIndex('testinstancequestions_unique', '{{%exam_testinstance_questions}}');
        $this->renameTable('{{%exam_testinstance_questions}}', '{{%quiz_testinstance_questions}}');
        $this->createIndex('questionID', '{{%quiz_testinstance_questions}}', ['questionID']);
        $this->createIndex(
            'testinstancequestions_unique',
            '{{%quiz_testinstance_questions}}',
            ['testinstanceID', 'questionID'],
            true
        );
        $this->addForeignKey(
            '{{%quiz_testinstance_questions_ibfk_1}}',
            '{{%quiz_testinstance_questions}}',
            ['questionID'],
            '{{%quiz_questions}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            '{{%quiz_testinstance_questions_ibfk_2}}',
            '{{%quiz_testinstance_questions}}',
            ['testinstanceID'],
            '{{%quiz_testinstances}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );

        //quiz_answers_submitted
        $this->dropForeignKey('{{%exam_answers_submitted_ibfk_1}}', '{{%exam_answers_submitted}}');
        $this->dropForeignKey('{{%exam_answers_submitted_ibfk_2}}', '{{%exam_answers_submitted}}');
        $this->dropIndex('{{%submittedanswers_ibfk_2}}', '{{%exam_answers_submitted}}');
        $this->dropIndex('submittedanswers_unique', '{{%exam_answers_submitted}}');
        $this->renameTable('{{%exam_answers_submitted}}', '{{%quiz_answers_submitted}}');
        $this->createIndex('answerID', '{{%quiz_answers_submitted}}', ['answerID']);
        $this->createIndex(
            'submittedanswers_unique',
            '{{%quiz_answers_submitted}}',
            ['testinstanceID', 'answerID'],
            true
        );
        $this->addForeignKey(
            '{{%quiz_answers_submitted_ibfk_1}}',
            '{{%quiz_answers_submitted}}',
            ['testinstanceID'],
            '{{%quiz_testinstances}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            '{{%quiz_answers_submitted_ibfk_2}}',
            '{{%quiz_answers_submitted}}',
            ['answerID'],
            '{{%quiz_answers}}',
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
        //quiz_questionsets
        $this->dropForeignKey('{{%quiz_questionsets_ibfk}}', '{{%quiz_questionsets}}');
        $this->renameTable('{{%quiz_questionsets}}', '{{%exam_questionsets}}');
        $this->addForeignKey(
            '{{%questionsets_ibfk}}',
            '{{%exam_questionsets}}',
            ['courseID'],
            '{{%courses}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );

        //quiz_questions
        $this->dropForeignKey('{{%quiz_questions_ibfk}}', '{{%quiz_questions}}');
        $this->renameTable('{{%quiz_questions}}', '{{%exam_questions}}');
        $this->addForeignKey(
            '{{%questions_ibfk}}',
            '{{%exam_questions}}',
            ['questionsetID'],
            '{{%exam_questionsets}}',
            ['id'],
            'CASCADE',
            'NO ACTION'
        );

        //quiz_answers
        $this->dropForeignKey('{{%quiz_answers_ibfk}}', '{{%quiz_answers}}');
        $this->renameTable('{{%quiz_answers}}', '{{%exam_answers}}');
        $this->addForeignKey(
            '{{%answers_ibfk}}',
            '{{%exam_answers}}',
            ['questionID'],
            '{{%exam_questions}}',
            ['id'],
            'CASCADE',
            'NO ACTION'
        );

        //quiz_tests
        $this->dropForeignKey('{{%quiz_tests_ibfk_1}}', '{{%quiz_tests}}');
        $this->dropForeignKey('{{%quiz_tests_ibfk_2}}', '{{%quiz_tests}}');
        $this->dropIndex('questionsetID', '{{%quiz_tests}}');
        $this->dropIndex('groupID', '{{%quiz_tests}}');
        $this->renameTable('{{%quiz_tests}}', '{{%exam_tests}}');
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

        //quiz_testinstances
        $this->dropForeignKey('{{%quiz_testinstances_ibfk_1}}', '{{%quiz_testinstances}}');
        $this->dropForeignKey('{{%quiz_testinstances_ibfk_2}}', '{{%quiz_testinstances}}');
        $this->dropIndex('userID', '{{%quiz_testinstances}}');
        $this->dropIndex('testID', '{{%quiz_testinstances}}');
        $this->renameTable('{{%quiz_testinstances}}', '{{%exam_testinstances}}');
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

        //quiz_testinstance_questions
        $this->dropForeignKey('{{%quiz_testinstance_questions_ibfk_1}}', '{{%quiz_testinstance_questions}}');
        $this->dropForeignKey('{{%quiz_testinstance_questions_ibfk_2}}', '{{%quiz_testinstance_questions}}');
        $this->dropIndex('questionID', '{{%quiz_testinstance_questions}}');
        $this->dropIndex('testinstancequestions_unique', '{{%quiz_testinstance_questions}}');
        $this->renameTable('{{%quiz_testinstance_questions}}', '{{%exam_testinstance_questions}}');
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

        //quiz_answers_submitted
        $this->dropForeignKey('{{%quiz_answers_submitted_ibfk_1}}', '{{%quiz_answers_submitted}}');
        $this->dropForeignKey('{{%quiz_answers_submitted_ibfk_2}}', '{{%quiz_answers_submitted}}');
        $this->dropIndex('answerID', '{{%quiz_answers_submitted}}');
        $this->dropIndex('submittedanswers_unique', '{{%quiz_answers_submitted}}');
        $this->renameTable('{{%quiz_answers_submitted}}', '{{%exam_answers_submitted}}');
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
}
