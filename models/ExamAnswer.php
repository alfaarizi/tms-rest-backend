<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "answers".
 *
 * @property int $id
 * @property string $text
 * @property int $correct
 * @property int $questionID
 *
 * @property ExamQuestion $question
 */
class ExamAnswer extends \yii\db\ActiveRecord
{
    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_UPDATE = 'update';

    public function scenarios()
    {
        return ArrayHelper::merge(parent::scenarios(), [
            self::SCENARIO_CREATE => ['text', 'correct', 'questionID'],
            self::SCENARIO_UPDATE => ['text', 'correct']
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%exam_answers}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['text', 'correct', 'questionID'], 'required'],
            [['correct'], 'boolean'],
            [['questionID'], 'integer'],
            [['text'], 'string', 'max' => 2500],
            [['text'], function ($attribute, $params, $validator) {
                $query = ExamAnswer::find()->where(["text" => $this->text,"questionID" => $this->questionID]);
                if (($this->id == null && !empty($query->all())) || !empty($query->andWhere(["<>", "id", $this->id])->all())) {
                    $validator->addError($this, $attribute, Yii::t('app', 'An answer with the same text already exists for that question'));
                }
            }],
            [['questionID'], 'exist', 'skipOnError' => true, 'targetClass' => ExamQuestion::class, 'targetAttribute' => ['questionID' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'text' => Yii::t('app', 'Text'),
            'correct' => Yii::t('app', 'Correct'),
            'questionID' => Yii::t('app', 'Question ID'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestion()
    {
        return $this->hasOne(ExamQuestion::class, ['id' => 'questionID']);
    }
}
