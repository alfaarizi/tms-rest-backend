<?php

namespace app\models;

use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;

/**
 * This is the model class for table "ip_addresses".
 *
 * @property integer $id
 * @property integer $submissionID
 * @property string $activity
 * @property string $translatedActivity
 * @property string $logTime
 * @property string $ipAddress
 *
 * @property-read Submission $submission
 */
class IpAddress extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    public const ACTIVITY_LOGIN = 'Login';
    public const ACTIVITY_SUBMISSION_UPLOAD = 'Submission upload';
    public const ACTIVITY_SUBMISSION_DOWNLOAD = 'Submission download';

    const ACTIVITY_VALUES = [
        self::ACTIVITY_LOGIN,
        self::ACTIVITY_SUBMISSION_UPLOAD,
        self::ACTIVITY_SUBMISSION_DOWNLOAD
    ];

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%ip_addresses}}';
    }

    public function rules(): array
    {
        return [
            [['activity', 'submissionID', 'ipAddress', 'logTime'], 'required'],
            [['submissionID'], 'integer'],
            [['activity', 'ipAddress'], 'string'],
            [['ipAddress'], 'ip',]
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'submissionID' => Yii::t('app', 'Submission ID'),
            'activity' => Yii::t('app', 'Activity'),
            'translatedActivity' => Yii::t('app', 'Activity'),
            'logTime' => Yii::t('app', 'Log time'),
            'ipAddress' => Yii::t('app', 'IP Address'),
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'submissionID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'activity' => new OAProperty(['type' => 'string', 'enum' => new OAList(self::ACTIVITY_VALUES)]),
            'translatedActivity' => new OAProperty(['type' => 'string']),
            'logTime' => new OAProperty(['type' => 'string']),
            'ipAddress' => new OAProperty(['type' => 'string'])
        ];
    }

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->logTime = date('Y-m-d H:i:s');
        if (isset(Yii::$app->request->userIP)) {
            $this->ipAddress = Yii::$app->request->userIP;
        }
    }

    public function getTranslatedActivity(): string
    {
        return Yii::t('app', $this->activity);
    }

    public function getSubmission(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Submission::class, ['id' => 'submissionID']);
    }
}
