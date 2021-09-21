<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "testinstancequestions".
 *
 * @property int $testinstanceID
 * @property int $questionID
 *
 * @property ExamTestInstance $testInstance
 * @property ExamQuestion $question
 */
class ExamTestInstanceQuestion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%exam_testinstance_questions}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['testinstanceID', 'questionID'], 'integer'],
            [['testinstanceID', 'questionID'], 'required'],
            [['testinstanceID', 'questionID'], 'unique', 'targetAttribute' => ['testinstanceID', 'questionID']],
            [['testinstanceID'], 'exist', 'skipOnError' => true, 'targetClass' => ExamTestInstance::class, 'targetAttribute' => ['testinstanceID' => 'id']],
            [['questionID'], 'exist', 'skipOnError' => true, 'targetClass' => ExamQuestion::class, 'targetAttribute' => ['questionID' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'testinstanceID' => Yii::t('app', 'Test Instance ID'),
            'questionID' => Yii::t('app', 'Question ID'),
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
    public function getQuestion()
    {
        return $this->hasOne(ExamQuestion::class, ['id' => 'questionID']);
    }
}
