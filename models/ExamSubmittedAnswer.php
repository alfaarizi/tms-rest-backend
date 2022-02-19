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
 * @property ExamTestInstance $testInstance
 * @property ExamAnswer $answer
 */
class ExamSubmittedAnswer extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%exam_answers_submitted}}';
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
            [['testinstanceID'], 'exist', 'skipOnError' => true, 'targetClass' => ExamTestInstance::class, 'targetAttribute' => ['testinstanceID' => 'id']],
            [['answerID'], 'exist', 'skipOnError' => true, 'targetClass' => ExamAnswer::class, 'targetAttribute' => ['answerID' => 'id']],
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

        $exists = ExamTestInstanceQuestion::find()
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
        return $this->hasOne(ExamTestInstance::class, ['id' => 'testinstanceID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAnswer()
    {
        return $this->hasOne(ExamAnswer::class, ['id' => 'answerID']);
    }
}
