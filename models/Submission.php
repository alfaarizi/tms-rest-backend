<?php

namespace app\models;

use app\behaviors\ISODateTimeBehavior;
use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\queries\SubmissionQuery;
use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;

/**
 * This is the model class for table "submission".
 *
 * @property integer $id
 * @property string|null $name
 * @property-read string $path
 * @property-read string $reportPath
 * @property-read string $basePath
 * @property string|null $uploadTime
 * @property integer $taskID
 * @property integer $uploaderID
 * @property string $status
 * @property boolean $isVersionControlled
 * @property float|null $grade
 * @property string $notes
 * @property integer $graderID
 * @property string $autoTesterStatus
 * @property string|null $errorMsg
 * @property integer $canvasID
 * @property integer $uploadCount
 * @property boolean $verified
 * @property integer|null $codeCheckerResultID
 * @property-read string $containerName
 * @property-read IpAddress[] $ipAddresses
 * @property-read string[] $detailedIpAddresses
 * @property-read string $safeErrorMsg
 *
 * @property Task $task
 * @property User $uploader
 * @property User $grader
 * @property CodeCheckerResult $codeCheckerResult
 * @property TestResult[] $testResults
 *
 */
class Submission extends File implements IOpenApiFieldTypes
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

    public const STATUS_UPLOADED = 'Uploaded';
    public const STATUS_ACCEPTED = 'Accepted';
    public const STATUS_REJECTED = 'Rejected';
    public const STATUS_LATE_SUBMISSION = 'Late Submission';
    public const STATUS_PASSED = 'Passed';
    public const STATUS_FAILED = 'Failed';
    public const STATUS_CORRUPTED = 'Corrupted';
    public const STATUS_NO_SUBMISSION = 'No Submission';

    public const STATUS_VALUES = [
        self::STATUS_UPLOADED,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_LATE_SUBMISSION,
        self::STATUS_PASSED,
        self::STATUS_FAILED,
        self::STATUS_NO_SUBMISSION,
        self::STATUS_CORRUPTED
    ];

    public const STATUS_GRADE_VALUES = [
        self::STATUS_UPLOADED,
        self::STATUS_ACCEPTED,
        self::STATUS_REJECTED,
        self::STATUS_LATE_SUBMISSION,
        self::STATUS_PASSED, // Required by a third party tool
        self::STATUS_FAILED, // Required by a third party tool
    ];

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_GRADE] = ['status', 'grade', 'notes'];
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
        return '{{%submissions}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['path', 'taskID', 'uploaderID', 'status', 'autoTesterStatus', 'verified'], 'required'],
            [['name', 'uploadTime'], 'required', 'when' => function ($submission) {
                return $submission->uploadCount > 0;
            }],
            [['name', 'uploadTime'], 'compare', 'compareValue' => 'null', 'when' => function ($submission) {
                return $submission->uploadCount === 0;
            }],
            [['uploadTime'], 'safe'],
            [['taskID', 'uploaderID', 'graderID'], 'integer'],
            [['status', 'reportPath'], 'string'],
            [['name'], 'string', 'max' => 200],
            [['grade'], 'number'],
            [['notes'], 'string'],
            [['isVersionControlled', 'verified'], 'boolean'],
            [['errorMsg'], 'string'],
            ['status', 'in', 'range' => self::STATUS_GRADE_VALUES, 'on' => self::SCENARIO_GRADE],
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
            'reportPath' => Yii::t('app', 'Report Path'),
            'uploadTime' => Yii::t('app', 'Upload Time'),
            'taskID' => Yii::t('app', 'Task ID'),
            'uploaderID' => Yii::t('app', 'Uploader ID'),
            'status' => Yii::t('app', 'Is Accepted'),
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
            'reportPath' => new OAProperty(['type' => 'string']),
            'uploadTime' => new OAProperty(['type' => 'string']),
            'taskID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'uploaderID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'status' => new OAProperty(['type' => 'string',  'enum' => new OAList(self::STATUS_VALUES)]),
            'autoTesterStatus' => new OAProperty(['type' => 'string',  'enum' => new OAList(self::AUTO_TESTER_STATUS_VALUES)]),
            'translatedStatus' => new OAProperty(['type' => 'string']),
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
     * @return SubmissionQuery the active query used by this class.
     */
    public static function find()
    {
        return new SubmissionQuery(get_called_class());
    }

    /**
     * Validates if the current status, validatorStatus pair is correct
     * @param $attribute
     * @param $params
     * @param $validator
     * @return void
     */
    public function validateAutoTesterStatus($attribute, $params, $validator)
    {
        if (
            $this->status === self::STATUS_PASSED && $this->autoTesterStatus !== self::AUTO_TESTER_STATUS_PASSED ||
            $this->status !== self::STATUS_UPLOADED && $this->autoTesterStatus === self::AUTO_TESTER_STATUS_IN_PROGRESS ||
            $this->status === self::STATUS_NO_SUBMISSION && $this->autoTesterStatus !== self::AUTO_TESTER_STATUS_NOT_TESTED
        ) {
            $this->addError(
                'autoTesterStatus',
                Yii::t('app', 'The current values of autoTesterStatus and status are not valid'),
            );
            return;
        }

        if ($this->status === self::STATUS_FAILED) {
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
                        Yii::t('app', 'The current values of autoTesterStatus and status are not valid'),
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
        if ($this->status !== Submission::STATUS_NO_SUBMISSION) {
            return parent::beforeDelete();
        }

        return \yii\db\ActiveRecord::beforeDelete();
    }

    /**
     * @inheritdoc
     */
    public function getBasePath(): string
    {
        return Yii::getAlias("@appdata/uploadedfiles/") .
            $this->taskID . '/' . strtolower($this->uploader->userCode);
    }

    /**
     * @inheritdoc
     */
    public function getPath(): string
    {
        return $this->basePath . '/' . $this->name;
    }

    /**
     * @return string
     */
    public function getReportPath(): string
    {
        $identifierLower = strtolower($this->uploader->userCode);
        return Yii::getAlias("@appdata/webreports/$this->taskID/$identifierLower/reports.tar");
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
        return $this->hasMany(TestResult::class, ['submissionID' => 'id']);
    }

    public function getTranslatedStatus()
    {
        return Yii::t('app', $this->status);
    }

    /**
     * Generates the container name for this submission.
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
            ->hasOne(WebAppExecution::class, ['submissionID' => 'id'])
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
     * Lists used and unique IP addresses for the current submission.
     *
     * For NOT Exam category tasks, only IP addresses used to upload this submission are listed.
     * In case of Exam category tasks, all IP addresses used for login, upload and download (any submission)
     * are returned in the timeframe of the exam.
     * @return array String array of IP addresses.
     */
    public function getIpAddresses(): array
    {
        // Not submitted solutions should not have IP addresses
        if ($this->status == self::STATUS_NO_SUBMISSION) {
            return [];
        }

        $addresses = $this->fetchIpAddresses()->all();
        return array_values(array_unique(ArrayHelper::getColumn($addresses, 'ipAddress')));
    }

    /**
     * Lists used IP addresses for the current submission
     *
     * For NOT Exam category tasks, only IP addresses used to upload this submission are listed.
     * In case of Exam category tasks, all IP addresses used for login, upload and download (any submission)
     * are returned in the timeframe of the exam.
     * @return array|IpAddress[] Array of IP addresses structures.
     */
    public function getDetailedIpAddresses(): array
    {
        // Not submitted solutions should not have IP addresses
        if ($this->status == self::STATUS_NO_SUBMISSION) {
            return [];
        }

        $addresses = $this->fetchIpAddresses()->all();
        usort($addresses, fn(IpAddress $a, IpAddress $b) => $a->logTime <=> $b->logTime);
        return $addresses;
    }

    /**
     * Lists used IP addresses for the current submission as a query.
     *
     * For NOT Exam category tasks, only IP addresses used to upload this submission are listed.
     * In case of Exam category tasks, all IP addresses used for login, upload and download (any submission)
     * are returned in the timeframe of the exam.
     */
    private function fetchIpAddresses(): \yii\db\ActiveQuery
    {
        // IP entries for this submission
        $selfActivities = IpAddress::find()
            ->where(['submissionID' => $this->id])
            ->orderBy('logTime');

        if ($this->task->category != Task::CATEGORY_TYPE_EXAMS || empty($this->task->available)) {
            return $selfActivities;
        }

        // In case of Exam tasks, fetch IP entries for other accessed submissions during the exam
        $sameUserActivities = IpAddress::find()
            ->alias('ip')
            ->joinWith('submission s')
            ->where(['s.uploaderID' => $this->uploaderID])
            ->andWhere(['>=', 'ip.logTime', $this->task->available])
            ->andWhere(['<=', 'ip.logTime', $this->task->hardDeadline])
            ->andWhere(
                [
                    'or',
                    ['not', ['ip.activity' => 'Login']],
                    ['ip.submissionID' => $this->id],
                ]
            )
            ->orderBy('logTime');

        return $selfActivities->union($sameUserActivities);
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
                return $this->task->showFullErrorMsg
                    ? $this->errorMsg
                    : Yii::t('app', 'Your solution passed the tests');
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
