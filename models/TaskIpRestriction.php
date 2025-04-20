<?php

namespace app\models;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "task_ip_restrictions".
 *
 * @property int $id
 * @property int $taskID
 * @property string $ipAddress
 * @property string $ipMask
 */
class TaskIpRestriction extends ActiveRecord implements IOpenApiFieldTypes
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%task_ip_restrictions}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['taskID', 'ipAddress', 'ipMask'], 'required'],
            [['taskID'], 'integer'],
            [['ipAddress', 'ipMask'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'taskID' => 'Task ID',
            'ipAddress' => 'IP Address',
            'ipMask' => 'IP Mask',
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['type' => 'integer']),
            'taskID' => new OAProperty(['type' => 'integer']),
            'ipAddress' => new OAProperty(['type' => 'string']),
            'ipMask' => new OAProperty(['type' => 'string']),
        ];
    }

    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'taskID']);
    }
}
