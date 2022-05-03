<?php

namespace app\models;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\queries\GroupQuery;
use app\validators\TimeZoneValidator;
use Yii;

/**
 * This is the model class for table "groups".
 *
 * @property integer $id
 * @property integer $number
 * @property integer $courseID
 * @property integer $semesterID
 * @property integer $synchronizerID
 * @property integer $canvasSectionID
 * @property integer $canvasCourseID
 * @property boolean $isExamGroup
 * @property string $timezone
 * @property-read boolean $isCanvasCourse
 * @property-read boolean $canvasCanBeSynchronized
 * @property-read ?string $canvasUrl
 *
 * @property Semester $semester
 * @property Course $course
 * @property Subscription[] $subscriptions
 * @property Task[] $tasks
 * @property InstructorGroup[] $instructorGroups
 * @property InstructorCourse[] $instructorCourses
 * @property User[] $instructors
 * @property User $synchronizer
 */
class Group extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_UPDATE = 'update';

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = ['number', 'courseID', 'isExamGroup', 'timezone'];
        $scenarios[self::SCENARIO_UPDATE] = ['number', 'isExamGroup', 'timezone'];

        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%groups}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['number', 'courseID', 'semesterID', 'synchronizerID', 'canvasSectionID', 'canvasCourseID'],
                'integer'
            ],
            [
                ['isExamGroup'],
                'boolean'
            ],
            [
                'number',
                'unique',
                'targetAttribute' => ['number', 'courseID', 'semesterID'],
                'skipOnEmpty' => true,
                'message' => Yii::t('app', 'The combination of Group Number, Course ID and Semester ID has already been taken.')
            ],
            [
                ['timezone'],
                'string'
            ],
            [
                ['courseID', 'timezone'],
                'required'
            ],
            [
                ['timezone'],
                TimeZoneValidator::class
            ],
            [
                ['courseID'],
                'exist',
                'skipOnError' => false,
                'targetClass' => Course::class,
                'targetAttribute' => ['courseID' => 'id']
            ],
            [
                ['semesterID'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Semester::class,
                'targetAttribute' => ['semesterID' => 'id']
            ],
            [
                ['synchronizerID'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['synchronizerID' => 'id']
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'number' => Yii::t('app', 'Group Code'),
            'courseID' => Yii::t('app', 'Course ID'),
            'semesterID' => Yii::t('app', 'Semester ID'),
            'synchronizerID' => Yii::t('app', 'Synchronizer ID'),
            'isExamGroup' => Yii::t('app', 'Exam Group'),
            'canvasSectionID' => Yii::t('app', 'Canvas Section'),
            'canvasCourseID' => Yii::t('app', 'Canvas Course'),
            'timezone' => Yii::t('app', 'Timezone')
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'number' => new OAProperty(['type' => 'integer']),
            'courseID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'semesterID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'synchronizerID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'canvasSectionID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'canvasCourseID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'canvasCanBeSynchronized' => new OAProperty(['type' => 'boolean']),
            'isCanvasCourse' => new OAProperty(['type' => 'boolean']),
            'isExamGroup' => new OAProperty(['type' => 'boolean']),
            'timezone' => new OAProperty(['type' => 'string', 'example' => 'Europe/Budapest']),
            'canvasUrl' => new OAProperty(['type' => 'string', 'nullable' => 'true']),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSemester()
    {
        return $this->hasOne(Semester::class, ['id' => 'semesterID']);
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
    public function getSubscriptions()
    {
        return $this->hasMany(Subscription::class, ['groupID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTasks()
    {
        return $this->hasMany(Task::class, ['groupID' => 'id']);
    }

    /**
     * @param int $nth The nth task to query.
     * @return \app\models\Task
     */
    public function getNthTask($nth = 1)
    {
        return $this->hasMany(Task::class, ['groupID' => 'id'])
            ->orderBy('hardDeadline')
            ->limit(1)
            ->offset($nth - 1)
            ->one();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstructorGroups()
    {
        return $this->hasMany(InstructorGroup::class, ['groupID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstructorCourses()
    {
        return $this->hasMany(InstructorCourse::class, ['courseID' => 'courseID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstructors()
    {
        return $this->hasMany(User::class, ['id' => 'userID'])
            ->viaTable('{{%instructor_groups}}', ['groupID' => 'id']);
    }

    /**
    * @return \yii\db\ActiveQuery
    */
    public function getSynchronizer()
    {
        return $this->hasOne(User::class, ['id' => 'synchronizerID']);
    }

    /**
     * {@inheritdoc}
     * @return GroupQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new GroupQuery(get_called_class());
    }

    /**
     * @return bool
     */
    public function getCanvasCanBeSynchronized()
    {
        return Yii::$app->params['canvas']['enabled'] &&
            ($this->isCanvasCourse || (count($this->tasks) === 0 && count($this->subscriptions) === 0));
    }

    /**
     * @return bool
     */
    public function getIsCanvasCourse()
    {
        return !is_null($this->canvasCourseID);
    }


    public function getCanvasUrl(): ?string
    {
        $canvasParams = Yii::$app->params['canvas'];
        return ($canvasParams['enabled'] && !is_null($this->canvasCourseID))
            ? $canvasParams['url'] . 'courses/' . $this->canvasCourseID
            : null;
    }
}
