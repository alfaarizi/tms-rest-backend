<?php

namespace app\models;

use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "codechecker_results".
 *
 * @property int $id
 * @property string $token
 * @property int $studentFileID
 * @property string $createdAt
 * @property string $status
 * @property string|null $stdout
 * @property string|null $stderr
 * @property string|null $runnerErrorMessage
 * @property-read string|null $htmlReportsDirPath
 * @property-read string|null $translatedStatus
 *
 * @property CodeCheckerReport[] $codeCheckerReports
 * @property StudentFile $studentFile
 */
class CodeCheckerResult extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    public const STATUS_IN_PROGRESS = 'In Progress';
    public const STATUS_NO_ISSUES = 'No Issues';
    public const STATUS_ISSUES_FOUND = 'Issues Found';
    public const STATUS_ANALYSIS_FAILED = 'Analysis Failed';
    public const STATUS_RUNNER_ERROR = 'Runner Error';

    const STATUS_VALUES = [
        self::STATUS_IN_PROGRESS,
        self::STATUS_NO_ISSUES,
        self::STATUS_ISSUES_FOUND,
        self::STATUS_ANALYSIS_FAILED,
        self::STATUS_RUNNER_ERROR
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%codechecker_results}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['token', 'studentFileID', 'createdAt', 'status'], 'required'],
            [['id', 'studentFileID'], 'integer'],
            [['createdAt'], 'safe'],
            [['status', 'stdout', 'stderr', 'runnerErrorMessage'], 'string'],
            [['id'], 'unique'],
            [['studentFileID'], 'exist', 'skipOnError' => true, 'targetClass' => StudentFile::class, 'targetAttribute' => ['studentFileID' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'studentFileID' => Yii::t('app', 'Student File ID'),
            'createdAt' => Yii::t('app', 'Created At'),
            'status' => Yii::t('app', 'Status'),
            'errorMessage' => Yii::t('app', 'Error Message'),
            'stdout' => Yii::t('app', 'Standard Output'),
            'stderr' => Yii::t('app', 'Standard Error'),
        ];
    }

    /**
     * Gets query for [[CodecheckerReports]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCodeCheckerReports(): ActiveQuery
    {
        return $this->hasMany(CodeCheckerReport::class, ['resultID' => 'id']);
    }

    /**
     * Gets query for [[StudentFile]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStudentFile(): ActiveQuery
    {
        return $this->hasOne(StudentFile::class, ['id' => 'studentFileID']);
    }

    public function getHtmlReportsDirPath(): ?string
    {
        $path = Yii::getAlias("@appdata/codechecker_html_reports/") . $this->id;
        return is_dir($path) ? $path : null;
    }

    public function getTranslatedStatus(): string
    {
        return Yii::t('app', $this->status);
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'token' => new OAProperty(['type' => 'string']),
            'studentFileID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'createdAt' => new OAProperty(['type' => 'string']),
            'status' => new OAProperty(['type' => 'string',  'enum' => new OAList(self::STATUS_VALUES)]),
            'translatedStatus' => new OAProperty(['type' => 'string']),
            'stdout' => new OAProperty(['type' => 'string', 'nullable' => 'true']),
            'stderr' => new OAProperty(['type' => 'string', 'nullable' => 'true']),
            'runnerErrorMessage' => new OAProperty(['type' => 'string', 'nullable' => 'true']),
        ];
    }
}
