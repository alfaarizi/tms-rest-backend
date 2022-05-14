<?php

namespace app\models;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\queries\CodeCompassInstanceQuery;
use Yii;

/**
 * This is the model class for table "codecompass_instances".
 *
 * @property int $id
 * @property int $studentFileId
 * @property int $instanceStarterUserId
 * @property int $port
 * @property string $containerId
 * @property string $status
 * @property string $errorLogs
 * @property string $creationTime
 * @property string $username
 * @property string $password
 *
 * @property StudentFile $studentFile
 */
class CodeCompassInstance extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_WAITING = 'WAITING';
    public const STATUS_STARTING = 'STARTING';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%codecompass_instances}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['studentFileId', 'status', 'instanceStarterUserId'], 'required'],
            [['studentFileId', 'instanceStarterUserId', 'port'], 'integer'],
            [['containerId'], 'string', 'max' => 50],
            [['status'], 'string', 'max' => 10],
            ['errorLogs', 'string'],
            [['username', 'password'], 'string', 'max' => 20],
            [['studentFileId'], 'exist', 'skipOnError' => true, 'targetClass' => StudentFile::className(), 'targetAttribute' => ['studentFileId' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'studentFileId' => Yii::t('app', 'Student File ID'),
            'containerId' => Yii::t('app', 'Container ID'),
            'status' => Yii::t('app', 'Status'),
            'instanceStarterUserId' => Yii::t('app', 'Instance Starter User ID'),
            'port' => Yii::t('app', 'Port'),
            'errorLogs' => Yii::t('app', 'Error Logs'),
            'creationTime' => Yii::t('app', 'Creation Time'),
            'username' => Yii::t('app', 'Username'),
            'password' => Yii::t('app', 'Password'),
        ];
    }

    /**
     * Gets query for [[StudentFile]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStudentFile()
    {
        return $this->hasOne(StudentFile::className(), ['id' => 'studentFileId']);
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'studentFileId' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'instanceStarterUserId' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'status' => new OAProperty(['type' => 'string']),
            'port' => new OAProperty(['type' => 'string']),
            'errorLogs' => new OAProperty(['type' => 'string']),
            'username' => new OAProperty(['type' => 'string']),
            'password' => new OAProperty(['type' => 'string']),
            'creationTime' => new OAProperty(['type' => 'string']),
            'containerId' => new OAProperty(['type' => 'string'])
        ];
    }

    public static function find()
    {
        return new CodeCompassInstanceQuery(get_called_class());
    }
}
