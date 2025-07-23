<?php

use app\models\QuizQuestionSet;
use yii\db\Migration;

class m250716_173258_add_question_number_to_questions extends Migration
{
    private const BATCH_SIZE = 1000;
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%quiz_questions}}', 'questionNumber', $this->integer());
        for ($setOffset = 0; $setOffset <= self::BATCH_SIZE; $setOffset += self::BATCH_SIZE) {
            $questionSets = QuizQuestionSet::find()->offset($setOffset)->limit(self::BATCH_SIZE)->all();
            foreach ($questionSets as $set) {
                for ($questionOffset = 0; $questionOffset <= self::BATCH_SIZE; $questionOffset += self::BATCH_SIZE) {
                    $questions = $set->getQuestions()->orderBy('id')->offset($questionOffset)->limit(self::BATCH_SIZE)->all();
                    foreach ($questions as $index => $question) {
                        $question->questionNumber = $index + 1;
                        $question->save(false);
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%questions}}', 'questionNumber');
    }
}
