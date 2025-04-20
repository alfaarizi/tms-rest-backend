<?php

namespace app\models;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "task_ip_restrictions".
 *
 * @property int $id
 * @property string $name
 * @property string $ipAddress
 * @property string $ipMask
 */
class IpRestriction extends ActiveRecord implements IOpenApiFieldTypes
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%ip_restrictions}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['name','ipAddress', 'ipMask'], 'required'],
            [['name','ipAddress', 'ipMask'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'ipAddress' => 'IP Address',
            'ipMask' => 'IP Mask',
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['type' => 'integer']),
            'name' => new OAProperty(['type' => 'string']),
            'ipAddress' => new OAProperty(['type' => 'string']),
            'ipMask' => new OAProperty(['type' => 'string']),
        ];
    }
}
