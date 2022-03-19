<?php

namespace app\models;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "questions".
 *
 * @property int $id
 * @property string $text
 * @property int $questionsetID
 *
 * @property ExamAnswer[] $answers
 * @property ExamQuestionSet $questionSet
 */
class ExamQuestion extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_UPDATE = 'update';

    public function scenarios()
    {
        return ArrayHelper::merge(parent::scenarios(), [
            self::SCENARIO_CREATE => ['text', 'questionsetID'],
            self::SCENARIO_UPDATE => ['text']
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%exam_questions}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['text', 'questionsetID'], 'required'],
            [['questionsetID'], 'integer'],
            [['text'], 'string', 'max' => 2500],
            [['questionsetID'], 'exist', 'skipOnError' => true, 'targetClass' => ExamQuestionSet::class, 'targetAttribute' => ['questionsetID' => 'id']],
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
            'questionsetID' => Yii::t('app', 'Questionset ID'),
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => "#/components/schemas/int_id"]),
            'text' => new OAProperty(['type' => 'string']),
            'questionsetID' => new OAProperty(['ref' => "#/components/schemas/int_id"]),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAnswers()
    {
        return $this->hasMany(ExamAnswer::class, ['questionID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCorrectAnswers()
    {
        return $this->hasMany(ExamAnswer::class, ['questionID' => 'id'])->where(['correct' => true]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestionSet()
    {
        return $this->hasOne(ExamQuestionSet::class, ['id' => 'questionsetID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTestInstances()
    {
        return $this->hasMany(ExamTestInstance::class, ['id' => 'testinstanceID'])
            ->viaTable('{{%exam_testinstance_questions}}', ['questionID' => 'id']);
    }
}
