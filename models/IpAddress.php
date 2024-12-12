<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ip_address".
 *
 * @property integer $id
 * @property integer $submissionId
 * @property string $type
 * @property string $logTime
 * @property string $ipAddress
 *
 * @property-read Submission $submission
 */
class IpAddress extends \yii\db\ActiveRecord
{
    public const TYPE_LOGIN = 'Login';
    public const TYPE_SUBMISSION_UPLOAD = 'Submission upload';
    public const TYPE_SUBMISSION_DOWNLOAD = 'Submission download';

    const TYPE_VALUES = [
        self::TYPE_LOGIN,
        self::TYPE_SUBMISSION_UPLOAD,
        self::TYPE_SUBMISSION_DOWNLOAD
    ];

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%ip_address}}';
    }

    public function rules(): array
    {
        return [
            [['type', 'submissionId', 'ipAddress', 'logTime'], 'required'],
            [['id'], 'unique'],
            [['id', 'submissionId'], 'integer'],
            [['type', 'ipAddress'], 'string'],
            [['ipAddress'], 'ip',]
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'submissionId' => Yii::t('app', 'Submission ID'),
            'type' => Yii::t('app', 'Type'),
            'logTime' => Yii::t('app', 'Log time'),
            'ipAddress' => Yii::t('app', 'IP Address'),
        ];
    }

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->logTime = date('Y-m-d H:i:s');
        $this->ipAddress = Yii::$app->request->userIP;
    }

    public function getSubmission(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Submission::class, ['id' => 'submissionId']);
    }
}
