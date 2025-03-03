<?php

namespace app\models;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;

/**
 * This is the model class for table "testinstancequestions".
 *
 * @property int $testinstanceID
 * @property int $questionID
 *
 * @property QuizTestInstance $testInstance
 * @property QuizQuestion $question
 */
class QuizTestInstanceQuestion extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%quiz_testinstance_questions}}';
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
            [['testinstanceID'], 'exist', 'skipOnError' => true, 'targetClass' => QuizTestInstance::class, 'targetAttribute' => ['testinstanceID' => 'id']],
            [['questionID'], 'exist', 'skipOnError' => true, 'targetClass' => QuizQuestion::class, 'targetAttribute' => ['questionID' => 'id']],
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

    public function fieldTypes(): array
    {
        return [
            'testinstanceID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'questionID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
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
    public function getQuestion()
    {
        return $this->hasOne(QuizQuestion::class, ['id' => 'questionID']);
    }
}
