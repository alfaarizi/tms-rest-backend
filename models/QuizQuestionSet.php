<?php

namespace app\models;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\queries\QuizQuestionSetQuery;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "questionsets".
 *
 * @property int $id
 * @property string $name
 * @property int $courseID
 *
 * @property Course $course
 * @property QuizTest[] $tests
 * @property QuizQuestion[] $questions
 */
class QuizQuestionSet extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_UPDATE = 'update';

    public function scenarios()
    {
        return ArrayHelper::merge(parent::scenarios(), [
            self::SCENARIO_CREATE => ['name', 'courseID'],
            self::SCENARIO_UPDATE => ['name', 'courseID']
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%quiz_questionsets}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'courseID'], 'required'],
            [['courseID'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['courseID'], 'exist', 'skipOnError' => true, 'targetClass' => Course::class, 'targetAttribute' => ['courseID' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'courseID' => Yii::t('app', 'Course ID'),
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'name' => new OAProperty(['type' => 'string']),
            'courseID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestions()
    {
        return $this->hasMany(QuizQuestion::class, ['questionsetID' => 'id'])->orderBy(['questionNumber' => SORT_ASC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(Course::class, ['id' => 'courseID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTests()
    {
        return $this->hasMany(QuizTest::class, ['questionsetID' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return QuizQuestionSetQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new QuizQuestionSetQuery(get_called_class());
    }
}
