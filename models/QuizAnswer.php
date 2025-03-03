<?php

namespace app\models;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "answers".
 *
 * @property int $id
 * @property string $text
 * @property boolean $correct
 * @property int $questionID
 *
 * @property QuizQuestion $question
 */
class QuizAnswer extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
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
        return '{{%quiz_answers}}';
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
                $query = QuizAnswer::find()->where(["text" => $this->text,"questionID" => $this->questionID]);
                if (($this->id == null && !empty($query->all())) || !empty($query->andWhere(["<>", "id", $this->id])->all())) {
                    $validator->addError($this, $attribute, Yii::t('app', 'An answer with the same text already exists for that question'));
                }
            }],
            [['questionID'], 'exist', 'skipOnError' => true, 'targetClass' => QuizQuestion::class, 'targetAttribute' => ['questionID' => 'id']],
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

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => "#/components/schemas/int_id"]),
            'text' => new OAProperty(['type' => 'string']),
            'correct' => new OAProperty(['type' => 'integer']),
            'questionID' => new OAProperty(['ref' => "#/components/schemas/int_id"]),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestion()
    {
        return $this->hasOne(QuizQuestion::class, ['id' => 'questionID']);
    }
}
