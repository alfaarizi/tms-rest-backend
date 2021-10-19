<?php

namespace app\models;

use app\behaviors\ISODateTimeBehavior;
use app\models\queries\ExamTestInstanceQuery;
use Yii;
use app\models\User;

/**
 * This is the model class for table "testinstances".
 *
 * @property int $id
 * @property string $starttime
 * @property string $finishtime
 * @property int $submitted
 * @property int $score
 * @property int $userID
 * @property int $testID
 * @property-read int $testDuration
 *
 * @property ExamSubmittedAnswer[] $submittedanswers
 * @property ExamTestInstanceQuestion[] $testinstancequestions
 * @property ExamAnswer[] $answers
 * @property ExamQuestion[] $questions
 * @property User $user
 * @property ExamTest $test
 */
class ExamTestInstance extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%exam_testinstances}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => ISODateTimeBehavior::class,
                'attributes' => ['starttime', 'finishtime']
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['score', 'userID', 'testID'], 'integer'],
            [['submitted'], 'boolean'],
            [['userID', 'testID'], 'required'],
            [['userID'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['userID' => 'id']],
            [['testID'], 'exist', 'skipOnError' => true, 'targetClass' => ExamTest::class, 'targetAttribute' => ['testID' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'starttime' => Yii::t('app', 'Starttime'),
            'finishtime' => Yii::t('app', 'Finishtime'),
            'submitted' => Yii::t('app', 'Submitted'),
            'score' => Yii::t('app', 'Score'),
            'userID' => Yii::t('app', 'User ID'),
            'testID' => Yii::t('app', 'Test ID'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTest()
    {
        return $this->hasOne(ExamTest::class, ['id' => 'testID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestions()
    {
        return $this->hasMany(ExamQuestion::class, ['id' => 'questionID'])
            ->viaTable('{{%exam_testinstance_questions}}', ['testinstanceID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAnswers()
    {
        return $this->hasMany(ExamAnswer::class, ['id' => 'answerID'])
            ->viaTable('{{%exam_answers_submitted}}', ['testinstanceID' => 'id']);
    }

    /**
     * Test duration returned in seconds.
     * @return int
     */
    public function getTestDuration()
    {
        return strtotime($this->finishtime) - strtotime($this->starttime);
    }


    /**
     * {@inheritdoc}
     * @return ExamTestInstanceQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ExamTestInstanceQuery(get_called_class());
    }
}
