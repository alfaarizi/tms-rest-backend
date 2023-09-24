<?php

namespace app\models;

use app\behaviors\ISODateTimeBehavior;
use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\queries\StudentFileQuery;
use Yii;
use yii\helpers\StringHelper;

/**
 * This is the model class for table "studentFiles".
 *
 * @property integer $id
 * @property string $name
 * @property-read string $path
 * @property-read string $basePath
 * @property string $uploadTime
 * @property integer $taskID
 * @property integer $uploaderID
 * @property string $isAccepted
 * @property boolean $isVersionControlled
 * @property float $grade
 * @property string $notes
 * @property integer $graderID
 * @property string $autoTesterStatus
 * @property string $errorMsg
 * @property integer $canvasID
 * @property integer $uploadCount
 * @property boolean $verified
 * @property string $codeCheckerResultID
 * @property-read string $containerName
 * @property-read array $ipAddresses
 * @property-read string $safeErrorMsg
 *
 * @property Task $task
 * @property User $uploader
 * @property User $grader
 * @property CodeCheckerResult $codeCheckerResult
 * @property TestResult[] $testResults
 *
 */
class StudentFile extends File implements IOpenApiFieldTypes
{
    public const SCENARIO_GRADE = 'grade';

    public const AUTO_TESTER_STATUS_NOT_TESTED = 'Not Tested';
    public const AUTO_TESTER_STATUS_LEGACY_FAILED = 'Legacy Failed';
    public const AUTO_TESTER_STATUS_INITIATION_FAILED = 'Initiation Failed';
    public const AUTO_TESTER_STATUS_COMPILATION_FAILED = 'Compilation Failed';
    public const AUTO_TESTER_STATUS_EXECUTION_FAILED = 'Execution Failed';
    public const AUTO_TESTER_STATUS_TESTS_FAILED = 'Tests Failed';
    public const AUTO_TESTER_STATUS_PASSED = 'Passed';
    public const AUTO_TESTER_STATUS_IN_PROGRESS = 'In Progress';
    public const PATH_OF_CORRUPTED_FILE = '/sampledata/uploadedfiles/corrupted_submission.zip';

    public const AUTO_TESTER_STATUS_VALUES = [
        self::AUTO_TESTER_STATUS_NOT_TESTED,
        self::AUTO_TESTER_STATUS_LEGACY_FAILED,
        self::AUTO_TESTER_STATUS_INITIATION_FAILED,
        self::AUTO_TESTER_STATUS_COMPILATION_FAILED,
        self::AUTO_TESTER_STATUS_EXECUTION_FAILED,
        self::AUTO_TESTER_STATUS_TESTS_FAILED,
        self::AUTO_TESTER_STATUS_PASSED,
        self::AUTO_TESTER_STATUS_IN_PROGRESS,
    ];

    public const IS_ACCEPTED_UPLOADED = 'Uploaded';
    public const IS_ACCEPTED_ACCEPTED = 'Accepted';
    public const IS_ACCEPTED_REJECTED = 'Rejected';
    public const IS_ACCEPTED_LATE_SUBMISSION = 'Late Submission';
    public const IS_ACCEPTED_PASSED = 'Passed';
    public const IS_ACCEPTED_FAILED = 'Failed';
    public const IS_ACCEPTED_CORRUPTED = 'Corrupted';
    public const IS_ACCEPTED_NO_SUBMISSION = 'No Submission';

    public const IS_ACCEPTED_VALUES = [
        self::IS_ACCEPTED_UPLOADED,
        self::IS_ACCEPTED_ACCEPTED,
        self::IS_ACCEPTED_REJECTED,
        self::IS_ACCEPTED_LATE_SUBMISSION,
        self::IS_ACCEPTED_PASSED,
        self::IS_ACCEPTED_FAILED,
        self::IS_ACCEPTED_NO_SUBMISSION,
        self::IS_ACCEPTED_CORRUPTED
    ];

    public const IS_ACCEPTED_GRADE_VALUES = [
        self::IS_ACCEPTED_UPLOADED,
        self::IS_ACCEPTED_ACCEPTED,
        self::IS_ACCEPTED_REJECTED,
        self::IS_ACCEPTED_LATE_SUBMISSION,
        self::IS_ACCEPTED_PASSED, // Required by a third party tool
        self::IS_ACCEPTED_FAILED, // Required by a third party tool
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
            [['path', 'taskID', 'uploaderID', 'isAccepted', 'autoTesterStatus', 'verified'], 'required'],
            [['name', 'uploadTime'], 'required', 'when' => function ($studentFile) {
                return $studentFile->uploadCount > 0;
            }],
            [['name', 'uploadTime'], 'compare', 'compareValue' => 'null', 'when' => function ($studentFile) {
                return $studentFile->uploadCount === 0;
            }],
            [['uploadTime'], 'safe'],
            [['taskID', 'uploaderID', 'graderID'], 'integer'],
            [['isAccepted'], 'string'],
            [['name'], 'string', 'max' => 200],
            [['grade'], 'number'],
            [['notes'], 'string'],
            [['isVersionControlled', 'verified'], 'boolean'],
            [['errorMsg'], 'string'],
            ['isAccepted', 'in', 'range' => self::IS_ACCEPTED_GRADE_VALUES, 'on' => self::SCENARIO_GRADE],
            ['autoTesterStatus', 'in', 'range' => self::AUTO_TESTER_STATUS_VALUES],
            ['autoTesterStatus', 'validateAutoTesterStatus'],
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
            'autoTesterStatus' => Yii::t('app', 'Automatic tester status'),
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
            'autoTesterStatus' => new OAProperty(['type' => 'string',  'enum' => new OAList(self::AUTO_TESTER_STATUS_VALUES)]),
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
     * {@inheritdoc}
     * @return StudentFileQuery the active query used by this class.
     */
    public static function find()
    {
        return new StudentFileQuery(get_called_class());
    }

    /**
     * Validates if the current isAccepted, validatorStatus pair is correct
     * @param $attribute
     * @param $params
     * @param $validator
     * @return void
     */
    public function validateAutoTesterStatus($attribute, $params, $validator)
    {
        if (
            $this->isAccepted === self::IS_ACCEPTED_PASSED && $this->autoTesterStatus !== self::AUTO_TESTER_STATUS_PASSED ||
            $this->isAccepted !== self::IS_ACCEPTED_UPLOADED && $this->autoTesterStatus === self::AUTO_TESTER_STATUS_IN_PROGRESS ||
            $this->isAccepted === self::IS_ACCEPTED_NO_SUBMISSION && $this->autoTesterStatus !== self::AUTO_TESTER_STATUS_NOT_TESTED
        ) {
            $this->addError(
                'autoTesterStatus',
                Yii::t('app', 'The current values of autoTesterStatus and isAccepted are not valid'),
            );
            return;
        }

        if ($this->isAccepted === self::IS_ACCEPTED_FAILED) {
            switch ($this->autoTesterStatus) {
                case self::AUTO_TESTER_STATUS_LEGACY_FAILED:
                case self::AUTO_TESTER_STATUS_INITIATION_FAILED:
                case self::AUTO_TESTER_STATUS_COMPILATION_FAILED:
                case self::AUTO_TESTER_STATUS_EXECUTION_FAILED:
                case self::AUTO_TESTER_STATUS_TESTS_FAILED:
                    return;
                default:
                    $this->addError(
                        'autoTesterStatus',
                        Yii::t('app', 'The current values of autoTesterStatus and isAccepted are not valid'),
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

    /** {@inheritdoc} */
    public function beforeDelete()
    {
        if ($this->isAccepted !== StudentFile::IS_ACCEPTED_NO_SUBMISSION) {
            return parent::beforeDelete();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getBasePath(): string
    {
        return Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/' .
            $this->taskID . '/' . strtolower($this->uploader->neptun);
    }

    /**
     * @inheritdoc
     */
    public function getPath(): string
    {
        return $this->basePath . '/' . $this->name;
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTestResults()
    {
        return $this->hasMany(TestResult::class, ['studentFileID' => 'id']);
    }

    public function getTranslatedIsAccepted()
    {
        return Yii::t('app', $this->isAccepted);
    }

    /**
     * Generates the container name for this studentfile.
     */
    public function getContainerName(): string
    {
        // Prefixing.
        return "tms_{$this->id}";
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
        switch ($this->autoTesterStatus) {
            case self::AUTO_TESTER_STATUS_NOT_TESTED:
                return null;
            case self::AUTO_TESTER_STATUS_LEGACY_FAILED:
                // Show errorMsg for old tasks, because it is now always possible to determine the status for them
                return $this->errorMsg;
            case self::AUTO_TESTER_STATUS_INITIATION_FAILED:
                return $this->task->showFullErrorMsg
                    ? $this->errorMsg
                    : Yii::t('app', 'The testing environment could\'t be initialized');
            case self::AUTO_TESTER_STATUS_COMPILATION_FAILED:
                return $this->task->showFullErrorMsg
                    ? $this->errorMsg
                    : Yii::t('app', 'The solution didn\'t compile');
            case self::AUTO_TESTER_STATUS_EXECUTION_FAILED:
                return $this->task->showFullErrorMsg
                    ? $this->errorMsg
                    : Yii::t('app', 'Some error happened executing the program');
            case self::AUTO_TESTER_STATUS_TESTS_FAILED:
                return $this->task->showFullErrorMsg
                    ? $this->errorMsg
                    : Yii::t('app', 'Your solution failed the tests');
            case self::AUTO_TESTER_STATUS_PASSED:
                return Yii::t('app', 'Your solution passed the tests');
            case self::AUTO_TESTER_STATUS_IN_PROGRESS:
                return Yii::t('app', 'Your solution is being tested');
            default:
                throw new \UnexpectedValueException('Invalid autoTesterStatus');
        }
    }

    public function getCodeCheckerResult()
    {
        return $this->hasOne(CodeCheckerResult::class, ['id' => 'codeCheckerResultID']);
    }
}
