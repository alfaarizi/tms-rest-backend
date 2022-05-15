<?php

namespace app\models;

use app\behaviors\ISODateTimeBehavior;
use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;
use yii\helpers\StringHelper;

/**
 * This is the model class for table "studentFiles".
 *
 * @property integer $id
 * @property string $name
 * @property-read string $path
 * @property string $uploadTime
 * @property integer $taskID
 * @property integer $uploaderID
 * @property string $isAccepted
 * @property boolean $isVersionControlled
 * @property float $grade
 * @property string $notes
 * @property integer $graderID
 * @property string $evaluatorStatus
 * @property string $errorMsg
 * @property integer $canvasID
 * @property integer $uploadCount
 * @property boolean $verified
 * @property Task $task
 * @property User $uploader
 * @property User $grader
 *
 * @property-read array $ipAddresses
 * @property-read string $safeErrorMsg
 */
class StudentFile extends File implements IOpenApiFieldTypes
{
    public const SCENARIO_GRADE = 'grade';

    public const EVALUATOR_STATUS_NOT_TESTED = 'Not Tested';
    public const EVALUATOR_STATUS_LEGACY_FAILED = 'Legacy Failed';
    public const EVALUATOR_STATUS_COMPILATION_FAILED = 'Compilation Failed';
    public const EVALUATOR_STATUS_EXECUTION_FAILED = 'Execution Failed';
    public const EVALUATOR_STATUS_TESTS_FAILED = 'Tests Failed';
    public const EVALUATOR_STATUS_PASSED = 'Passed';

    public const EVALUATOR_STATUS_VALUES = [
        self::EVALUATOR_STATUS_NOT_TESTED,
        self::EVALUATOR_STATUS_LEGACY_FAILED,
        self::EVALUATOR_STATUS_COMPILATION_FAILED,
        self::EVALUATOR_STATUS_EXECUTION_FAILED,
        self::EVALUATOR_STATUS_TESTS_FAILED,
        self::EVALUATOR_STATUS_PASSED,
    ];

    public const IS_ACCEPTED_UPLOADED = 'Uploaded';
    public const IS_ACCEPTED_ACCEPTED = 'Accepted';
    public const IS_ACCEPTED_REJECTED = 'Rejected';
    public const IS_ACCEPTED_LATE_SUBMISSION = 'Late Submission';
    public const IS_ACCEPTED_PASSED = 'Passed';
    public const IS_ACCEPTED_FAILED = 'Failed';

    public const IS_ACCEPTED_VALUES = [
        self::IS_ACCEPTED_UPLOADED,
        self::IS_ACCEPTED_ACCEPTED,
        self::IS_ACCEPTED_REJECTED,
        self::IS_ACCEPTED_LATE_SUBMISSION,
        self::IS_ACCEPTED_PASSED,
        self::IS_ACCEPTED_FAILED
    ];

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_GRADE] = ['isAccepted', 'grade', 'notes'];
        return $scenarios;
    }


    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => ISODateTimeBehavior::class,
                'attributes' => ['uploadTime']
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%student_files}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'path', 'taskID', 'uploaderID', 'isAccepted', 'evaluatorStatus', 'verified'], 'required'],
            [['uploadTime'], 'safe'],
            [['taskID', 'uploaderID', 'graderID'], 'integer'],
            [['isAccepted'], 'string'],
            [['name'], 'string', 'max' => 200],
            [['grade'], 'number'],
            [['notes'], 'string'],
            [['isVersionControlled', 'verified'], 'boolean'],
            [['errorMsg'], 'string'],
            ['isAccepted', 'in', 'range' => self::IS_ACCEPTED_VALUES, 'on' => self::SCENARIO_GRADE],
            ['evaluatorStatus', 'in', 'range' => self::EVALUATOR_STATUS_VALUES],
            ['evaluatorStatus', 'validateEvaluatorStatus'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'path' => Yii::t('app', 'Path'),
            'uploadTime' => Yii::t('app', 'Upload Time'),
            'taskID' => Yii::t('app', 'Task ID'),
            'uploaderID' => Yii::t('app', 'Uploader ID'),
            'isAccepted' => Yii::t('app', 'Is Accepted'),
            'isVersionControlled' => Yii::t('app', 'Version control'),
            'grade' => Yii::t('app', 'Grade'),
            'notes' => Yii::t('app', 'Notes'),
            'graderID' => Yii::t('app', 'Graded By'),
            'errorMsg' => Yii::t('app', 'Error Message'),
            'canvasID' => Yii::t('app', 'Canvas id'),
            'evaluatorStatus' => Yii::t('app', 'Evaluator status'),
            'uploadCount' => Yii::t('app', 'Upload Count'),
            'verified' => Yii::t('app', 'Verified'),
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'name' => new OAProperty(['type' => 'string']),
            'path' => new OAProperty(['type' => 'string']),
            'uploadTime' => new OAProperty(['type' => 'string']),
            'taskID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'uploaderID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'isAccepted' => new OAProperty(['type' => 'string',  'enum' => new OAList(self::IS_ACCEPTED_VALUES)]),
            'evaluatorStatus' => new OAProperty(['type' => 'string',  'enum' => new OAList(self::EVALUATOR_STATUS_VALUES)]),
            'translatedIsAccepted' => new OAProperty(['type' => 'string']),
            'isVersionControlled' => new OAProperty(['type' => 'string']),
            'grade' => new OAProperty(['type' => 'number', 'format' => 'float']),
            'notes' => new OAProperty(['type' => 'string']),
            'graderID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'errorMsg' => new OAProperty(['type' => 'string']),
            'canvasID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'groupID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'uploadCount' => new OAProperty(['type' => 'number', 'format' => 'integer']),
            'verified' => new OAProperty(['type' => 'boolean']),
        ];
    }

    /**
     * @inheritdocs
     */
    public function afterFind()
    {
        parent::afterFind();
        // Convert integer (0 or 1) values to boolean values for consistency
        $this->verified = ($this->verified == 1);
    }

    /**
     * Validates if the current isAccepted, validatorStatus pair is correct
     * @param $attribute
     * @param $params
     * @param $validator
     * @return void
     */
    public function validateEvaluatorStatus($attribute, $params, $validator)
    {
        if ($this->isAccepted === self::IS_ACCEPTED_PASSED && $this->evaluatorStatus !== self::EVALUATOR_STATUS_PASSED) {
            $this->addError(
                'evaluatorStatus',
                Yii::t('app', 'The current values of evaluatorStatus and isAccepted are not valid'),
            );
            return;
        }

        if ($this->isAccepted === self::IS_ACCEPTED_FAILED) {
            switch ($this->evaluatorStatus) {
                case self::EVALUATOR_STATUS_LEGACY_FAILED:
                case self::EVALUATOR_STATUS_COMPILATION_FAILED:
                case self::EVALUATOR_STATUS_EXECUTION_FAILED:
                case self::EVALUATOR_STATUS_TESTS_FAILED:
                    return;
                default:
                    $this->addError(
                        'evaluatorStatus',
                        Yii::t('app', 'The current values of evaluatorStatus and isAccepted are not valid'),
                    );
                    return;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $this->errorMsg = StringHelper::truncate($this->errorMsg, 65000);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getPath(): string
    {
        return Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/' .
            $this->taskID . '/' . strtolower($this->uploader->neptun) . '/' .
            $this->name;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'taskID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUploader()
    {
        return $this->hasOne(User::class, ['id' => 'uploaderID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGrader()
    {
        return $this->hasOne(User::class, ['id' => 'graderID']);
    }

    public function getTranslatedIsAccepted()
    {
        return Yii::t('app', $this->isAccepted);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExecution()
    {
        return $this
            ->hasOne(WebAppExecution::class, ['studentFileID' => 'id'])
            ->onCondition(['instructorID' => Yii::$app->user->id]);
    }

    /**
     * @return string|null
     */
    public function getDelay()
    {
        $uploadTime = $this->uploadTime;
        if (is_null($uploadTime)) {
            return null;
        }
        $softDeadline = $this->task->softDeadline;

        if (
            !empty($softDeadline) &&
            strtotime($uploadTime) > strtotime($softDeadline)
        ) {
            $timezone = new \DateTimeZone($this->task->group->timezone);
            $softDeadlineInTime = new \DateTime($softDeadline);
            $softDeadlineInTime->setTimezone($timezone);
            $uploadTimeInTime = new \DateTime($uploadTime);
            $uploadTimeInTime->setTimezone($timezone);
            $diff = $softDeadlineInTime->diff($uploadTimeInTime);
            $delay = $diff->days + ($diff->h || $diff->i || $diff->s || $diff->f ? 1 : 0);

            return Yii::t(
                'app',
                '+{days} days',
                ['days' => $delay]
            );
        }

        return null;
    }

    /**
     * Lists unique upload ip addresses for the current file from the log messages
     * @return array
     */
    public function getIpAddresses(): array
    {
        $logs = Log::find()
            ->select(['prefix'])
            ->andWhere(['category' => 'app\modules\student\controllers\StudentFilesController::saveFile'])
            ->andWhere(['level' => 4])
            ->andWhere(['like', 'prefix', "({$this->uploader->neptun})"])
            ->andWhere(['like', 'message', 'A new solution has been uploaded for%', false])
            ->andWhere(['like', 'message', "%({$this->taskID})", false])
            ->distinct()
            ->all();

        return array_map(function (Log $log) {
            preg_match_all('/\[([^]]*)]/', $log->prefix, $prefixSections, PREG_SET_ORDER);
            return $prefixSections[0][1];
        }, $logs);
    }

    /**
     * Replaces full error message with a generic one if showFullErrorMsg is disabled
     * @throws \UnexpectedValueException Invalid evaluator status
     */
    public function getSafeErrorMsg(): ?string
    {
        switch ($this->evaluatorStatus) {
            case self::EVALUATOR_STATUS_NOT_TESTED:
                return null;
            case self::EVALUATOR_STATUS_LEGACY_FAILED:
                // Show errorMsg for old tasks, because it is now always possible to determine the status for them
                return $this->errorMsg;
            case self::EVALUATOR_STATUS_COMPILATION_FAILED:
                return $this->task->showFullErrorMsg
                    ? $this->errorMsg
                    : Yii::t('app', 'The solution didn\'t compile');
            case self::EVALUATOR_STATUS_EXECUTION_FAILED:
                return $this->task->showFullErrorMsg
                    ? $this->errorMsg
                    : Yii::t('app', 'Some error happened executing the program');
            case self::EVALUATOR_STATUS_TESTS_FAILED:
                return $this->task->showFullErrorMsg
                    ? $this->errorMsg
                    : Yii::t('app', 'Your solution failed the tests');
            case self::EVALUATOR_STATUS_PASSED:
                return Yii::t('app', 'Your solution passed the tests');
            default:
                throw new \UnexpectedValueException('Invalid evaluatorStatus');
        }
    }
}
