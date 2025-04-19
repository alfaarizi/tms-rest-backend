<?php

namespace app\models;

use app\behaviors\ISODateTimeBehavior;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\queries\QuizTestInstanceQuery;
use Yii;
use app\models\User;

/**
 * This is the model class for table "testinstances".
 *
 * @property int $id
 * @property string|null $starttime
 * @property string $finishtime
 * @property boolean $submitted
 * @property int $score
 * @property int $userID
 * @property int $testID
 * @property string|null $token
 * @property-read boolean $isUnlocked
 * @property-read int $testDuration
 *
 * @property QuizSubmittedAnswer[] $submittedanswers
 * @property QuizTestInstanceQuestion[] $testinstancequestions
 * @property QuizAnswer[] $answers
 * @property QuizQuestion[] $questions
 * @property User $user
 * @property QuizTest $test
 */
class QuizTestInstance extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%quiz_testinstances}}';
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
            [['token'], 'string', 'max' => 255],
            [['testID'], 'exist', 'skipOnError' => true, 'targetClass' => QuizTest::class, 'targetAttribute' => ['testID' => 'id']],
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
            'token' => Yii::t('app', 'Token'),
            'testID' => Yii::t('app', 'Test ID'),
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'starttime' => new OAProperty(['type' => 'string']),
            'finishtime' => new OAProperty(['type' => 'string']),
            'submitted' => new OAProperty(['type' => 'integer']),
            'score' => new OAProperty(['type' => 'integer']),
            'userID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'token' => new OAProperty(['type' => 'string']),
            'isUnlocked' => new OAProperty(['type' => 'boolean']),
            'testID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
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
        return $this->hasOne(QuizTest::class, ['id' => 'testID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestions()
    {
        return $this->hasMany(QuizQuestion::class, ['id' => 'questionID'])
            ->viaTable('{{%quiz_testinstance_questions}}', ['testinstanceID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAnswers()
    {
        return $this->hasMany(QuizAnswer::class, ['id' => 'answerID'])
            ->viaTable('{{%quiz_answers_submitted}}', ['testinstanceID' => 'id']);
    }

    /**
     * Test duration returned in seconds.
     * @return int
     */
    public function getTestDuration()
    {
        return strtotime($this->finishtime) - strtotime($this->starttime);
    }

    public function getIsUnlocked(): bool
    {
        if (!$this->test->isPasswordProtected) {
            return true;
        }

        $currentToken = AccessToken::getCurrent();
        $currentToken = $currentToken != null
            ? $currentToken->token
            : null;

        return !is_null($this->token) && !is_null($currentToken) && $this->token === $currentToken;
    }


    /**
     * {@inheritdoc}
     * @return QuizTestInstanceQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new QuizTestInstanceQuery(get_called_class());
    }
}
