<?php

namespace app\models;

use app\behaviors\ISODateTimeBehavior;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\queries\WebAppExecutionQuery;
use Yii;

/**
 * This is the model class for table "remote_executions"
 *
 * @property integer $id
 * @property integer $submissionID
 * @property integer $instructorID
 * @property string $startedAt
 * @property string $shutdownAt
 * @property integer $port
 * @property string $dockerHostUrl
 * @property string $containerName
 *
 * @property Submission $submission
 * @property User $instructor
 *
 * @property-read string $url
 */
class WebAppExecution extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    public static function find(): WebAppExecutionQuery
    {
        return new WebAppExecutionQuery(get_called_class());
    }

    public static function tableName(): string
    {
        return '{{%web_app_executions}}';
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => ISODateTimeBehavior::class,
                'attributes' => ['startedAt', 'shutdownAt']
            ]
        ];
    }

    public function rules(): array
    {
        return [
            [['submissionID', 'instructorID', 'dockerHostUrl', 'port'], 'required'],
            [['submissionID', 'instructorID', 'port'], 'integer'],
            [['containerName', 'dockerHostUrl'], 'string'],
            [
                ['submissionID'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Submission::class,
                'targetAttribute' => ['submissionID' => 'id']
            ],
            [
                ['instructorID'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['instructorID' => 'id']
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'submissionID' => Yii::t('app', 'Student File ID'),
            'instructorID' => Yii::t('app', 'Instructor ID'),
            'startedAt' => Yii::t('app', 'Started at'),
            'shutdownAt' => Yii::t('app', 'Shut down at'),
            'port' => Yii::t('app', 'Port'),
            'dockerHostUrl' => Yii::t('app', 'Docker host URL'),
            'containerName' => Yii::t('app', 'Container Name'),
            'submission' => Yii::t('app', 'Submission'),
            'instructor' => Yii::t('app', 'Instructor'),
            'url' => Yii::t('app', 'URL')
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'submissionID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'instructorID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'taskID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'startedAt' => new OAProperty(['type' => 'string', 'example' => '2022-01-01T23:59:00+00:00']),
            'shutdownAt' => new OAProperty(['type' => 'string', 'example' => '2022-01-01T23:59:00+00:00']),
            'port' => new OAProperty(['type' => 'integer']),
            'dockerHostUrl' => new OAProperty(['type' => 'string']),
            'containerName' => new OAProperty(['type' => 'string']),
            'url' => new OAProperty(['type' => 'string']),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubmission(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Submission::class, ['id' => 'submissionID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstructor(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'instructorID']);
    }

    /**
     * Creates the public facing url for this web app execution instance.
     * @return string
     */
    public function getUrl(): string
    {
        if (Yii::$app->params['evaluator']['webApp']['gateway']['enabled']) {
            return Yii::$app->params['evaluator']['webApp']['gateway']['url'] . '/' . $this->containerName;
        } else {
            return $this->dockerHostUrl . ':' . $this->port;
        }
    }
}
