<?php

namespace app\models;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;

/**
 * This is the model class for table "submittedanswer".
 *
 * @property int $testinstanceID
 * @property int $answerID
 *
 * @property QuizTestInstance $testInstance
 * @property QuizAnswer $answer
 */
class QuizSubmittedAnswer extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%quiz_answers_submitted}}';
    }

    public function fieldTypes(): array
    {
        return [
            'testinstanceID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'answerID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios[self::SCENARIO_DEFAULT] = ['!testinstanceID', 'answerID'];

        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // testInstanceID is marked as unsafe for mass assignment (!testinstanceID)
            [['testinstanceID'], 'required'],
            [['testinstanceID', 'answerID'], 'integer'],
            [['answerID'], 'unique', 'skipOnEmpty' => true, 'targetAttribute' => ['testinstanceID', 'answerID']],
            [['testinstanceID'], 'exist', 'skipOnError' => true, 'targetClass' => QuizTestInstance::class, 'targetAttribute' => ['testinstanceID' => 'id']],
            [['answerID'], 'exist', 'skipOnError' => true, 'targetClass' => QuizAnswer::class, 'targetAttribute' => ['answerID' => 'id']],
            ['answerID', 'validateAnswerIDDuplicate'],
            ['answerID', 'validateAnswerIDCorrectTestInstance'],
        ];
    }

    public function validateAnswerIDDuplicate($attribute, $params, $validator)
    {
        if (!$this->answer && $this->testInstance) {
            return;
        }

        $exists = $this->testInstance
            ->getAnswers()
            ->select('id')
            ->where(['questionID' => $this->answer->questionID])
            ->exists();

        if ($exists) {
            $this->addError('answerID', 'Answer is already saved for this question');
        }
    }

    public function validateAnswerIDCorrectTestInstance($attribute, $params, $validator)
    {
        if (!$this->answer && $this->testInstance) {
            return;
        }

        $exists = QuizTestInstanceQuestion::find()
            ->where(['testinstanceID' => $this->testinstanceID])
            ->andWhere(['questionID' => $this->answer->questionID])
            ->exists();

        if (!$exists) {
            $this->addError('answerID', 'This answer belongs to a different test instance');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'testinstanceID' => Yii::t('app', 'Test Instance ID'),
            'answerID' => Yii::t('app', 'Answer ID'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTestInstance()
    {
        return $this->hasOne(QuizTestInstance::class, ['id' => 'testinstanceID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAnswer()
    {
        return $this->hasOne(QuizAnswer::class, ['id' => 'answerID']);
    }
}
