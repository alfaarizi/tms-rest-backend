<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;

/**
 * @property integer $id
 * @property string $password
 * @property boolean $disableIpCheck
 */
class VerifyItemResource extends \app\models\Model implements IOpenApiFieldTypes
{
    public $id;
    public $password;
    public $disableIpCheck;

    public function rules(): array
    {
        return [
            [['id'], 'number'],
            [['id', 'password', 'disableIpCheck'], 'required'],
            [['password'], 'string', 'max' => 255],
            [['disableIpCheck'], 'boolean'],
        ];
    }

    public function fields(): array
    {
        return  ['id', 'password', 'disableIpCheck'];
    }

    public function extraFields(): array
    {
        return [];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'password' => new OAProperty(['type' => 'string']),
            'disableIpCheck' => new OAProperty(['type' => 'boolean']),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'password' => Yii::t('app', 'Password'),
            'disableIpCheck' => Yii::t('app', 'Disable IP address check'),
        ];
    }
}
