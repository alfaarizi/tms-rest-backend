<?php

namespace app\models;

use app\behaviors\ISODateTimeBehavior;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\queries\ExamTestQuery;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "tests".
 *
 * @property int $id
 * @property string $name
 * @property int $questionamount
 * @property bool $duration
 * @property bool $shuffled
 * @property int $unique
 * @property string $availablefrom
 * @property string $availableuntil
 * @property int $groupID
 * @property int $questionsetID
 * @property-read string timezone
 *
 * @property ExamTestInstance[] $testinstances
 * @property ExamQuestionSet $questionSet
 * @property Group $group
 */
class ExamTest extends ActiveRecord implements IOpenApiFieldTypes
{
    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_UPDATE = 'update';

    public function scenarios()
    {
        return ArrayHelper::merge(
            parent::scenarios(),
            [
                self::SCENARIO_CREATE => [
                    'name',
                    'questionamount',
                    'duration',
                    'shuffled',
                    'unique',
                    'availablefrom',
                    'availableuntil',
                    'groupID',
                    'questionsetID'
                ],
                self::SCENARIO_UPDATE => [
                    'name',
                    'questionamount',
                    'duration',
                    'shuffled',
                    'unique',
                    'availablefrom',
                    'availableuntil',
                    'groupID',
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%exam_tests}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => ISODateTimeBehavior::class,
                'attributes' => ['availablefrom', 'availableuntil']
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [
                ['name', 'availablefrom', 'availableuntil', 'groupID', 'questionsetID', 'questionamount', 'duration'],
                'required'
            ],
            [['questionamount', 'duration', 'groupID', 'questionsetID'], 'integer'],
            [['shuffled', 'unique'], 'boolean'],
            [['availablefrom', 'availableuntil'], 'safe'],
            [['questionamount', 'duration'], 'integer', 'min' => 1],
            [['name'], 'string', 'max' => 45],
            [
                ['questionsetID'],
                'exist',
                'skipOnError' => true,
                'targetClass' => ExamQuestionSet::class,
                'targetAttribute' => ['questionsetID' => 'id']
            ],
            [
                ['groupID'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Group::class,
                'targetAttribute' => ['groupID' => 'id']
            ],
            ['groupID', 'validateGroupID', 'skipOnError' => true],
            [
                ['questionamount'],
                function ($attribute, $params, $validator) {
                    $questions = ExamQuestion::find()->where(['questionsetID' => $this->questionsetID])->count();
                    if ($this->questionamount > $questions) {
                        $validator->addError(
                            $this,
                            $attribute,
                            Yii::t("app", "This question set doesn't have enough questions")
                        );
                    }
                }
            ],
            [
                ['availablefrom'],
                function ($attribute, $params, $validator) {
                    if (strtotime($this->availablefrom) > strtotime($this->availableuntil)) {
                        $validator->addError(
                            $this,
                            $attribute,
                            Yii::t("app", "Start of availability must be before end of availability")
                        );
                    }
                }
            ],
            [
                ['availableuntil'],
                function ($attribute, $params, $validator) {
                    if (strtotime($this->availableuntil) < time()) {
                        $validator->addError(
                            $this,
                            $attribute,
                            Yii::t("app", "End of availability must be after current date")
                        );
                    }
                }
            ],
            [
                ['duration'],
                function ($attribute, $params, $validator) {
                    if ($this->duration * 60 > strtotime($this->availableuntil) - strtotime($this->availablefrom)) {
                        $validator->addError(
                            $this,
                            $attribute,
                            Yii::t("app", "Duration cannot be longer than availability")
                        );
                    }
                }
            ]
        ];
    }

    /**
     * Checks if the given group belongs to the correct course
     */
    public function validateGroupID($attribute, $params, $validator)
    {
        $courseID = $this->questionSet->courseID;
        if ($this->group->courseID != $courseID) {
            $this->addError($attribute, 'This group belongs to a different course.');
        }
    }

    /**
     * Finalize test, create test instance for all users.
     *
     * @return void
     * @throws \yii\db\Exception
     */
    public function finalize()
    {
        $groupID = $this->groupID;
        $group = Group::findOne($groupID);
        $subscriptions = Subscription::find()->select('userID')->where(
            [
                'groupID' => $groupID,
                'semesterID' => $group->semesterID,
                'isAccepted' => true
            ]
        );
        $count = User::find()->where(['in', 'id', $subscriptions])->count();

        if ($count < 1) {
            throw new \LengthException(Yii::t('app', 'The selected group is empty. Please add at least one student!'));
        }


        $users = User::find()->where(['in', 'id', $subscriptions])->all();

        $batchTests = array();
        foreach ($users as $user) {
            $testInstance = new ExamTestInstance();
            $testInstance->score = 0;
            $testInstance->submitted = 0;
            $testInstance->userID = $user->id;
            $testInstance->testID = $this->id;
            $batchTests[] = $testInstance->attributes;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $testAttr = ['id', 'starttime', 'finishtime', 'submitted', 'score', 'userID', 'testID'];
            Yii::$app->db->createCommand()->batchInsert(ExamTestInstance::tableName(), $testAttr, $batchTests)->execute();

            //Shuffle array of questions and slice the first n where n is the question amount
            $questions = ExamQuestion::find()->where(['questionsetID' => $this->questionsetID])->all();
            shuffle($questions);
            $chosen = array_slice($questions, 0, $this->questionamount);
            $batchQuestions = array();
            $questionAttr = ['questionID', 'testinstanceID'];
            foreach (ExamTestInstance::findAll(['testID' => $this->id]) as $testInstance) {
                //In case of unique test instances questions are being shuffled for every user
                if ($this->unique) {
                    shuffle($questions);
                    $chosen = array_slice($questions, 0, $this->questionamount);
                    foreach ($chosen as $question) {
                        $batchQuestions[] = [$question->id, $testInstance->id];
                    }
                } else {
                    foreach ($chosen as $question) {
                        $batchQuestions[] = [$question->id, $testInstance->id];
                    }
                }
            }
            Yii::$app->db->createCommand()->batchInsert(
                ExamTestInstanceQuestion::tableName(),
                $questionAttr,
                $batchQuestions
            )->execute();

            $transaction->commit();
            Yii::info(
                "A new test has been finalized: $this->name ($this->id)." . PHP_EOL .
                "Course: {$this->group->course->name}" . PHP_EOL .
                "Group number: {$this->group->number}, groupID: {$this->groupID}",
                __METHOD__
            );
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'questionamount' => Yii::t('app', 'Question amount'),
            'duration' => Yii::t('app', 'Duration'),
            'shuffled' => Yii::t('app', 'Shuffled'),
            'unique' => Yii::t('app', 'Unique'),
            'availablefrom' => Yii::t('app', 'Available from'),
            'availableuntil' => Yii::t('app', 'Available until'),
            'groupID' => Yii::t('app', 'Group ID'),
            'questionsetID' => Yii::t('app', 'Question set ID'),
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'name' => new OAProperty(['type' => 'string']),
            'questionamount' => new OAProperty(['type' => 'integer']),
            'duration' => new OAProperty(['type' => 'integer']),
            'shuffled' => new OAProperty(['type' => 'integer']),
            'unique' => new OAProperty(['type' => 'integer']),
            'availablefrom' => new OAProperty(['type' => 'string']),
            'availableuntil' => new OAProperty(['type' => 'string']),
            'groupID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'questionsetID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getTestInstances()
    {
        return $this->hasMany(ExamTestInstance::class, ['testID' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getQuestionSet()
    {
        return $this->hasOne(ExamQuestionSet::class, ['id' => 'questionsetID']);
    }

    /**
     * @return ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(Group::class, ['id' => 'groupID']);
    }

    /**
     * {@inheritdoc}
     * @return ExamTestQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ExamTestQuery(get_called_class());
    }
}
