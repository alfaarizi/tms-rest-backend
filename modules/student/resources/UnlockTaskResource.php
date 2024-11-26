<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;
use Yii;


/**
 * @property string $password
 */
class UnlockTaskResource extends Model implements IOpenApiFieldTypes
{
    public $password;

    public function rules(): array
    {
        return [
            [['password'], 'required'],
            [['password'], 'string', 'max' => 255],
        ];
    }

    public function fields(): array
    {
        return  ['password'];
    }

    public function extraFields(): array
    {
        return [];
    }

    public function fieldTypes(): array
    {
        return [
            'password' => new OAProperty(['type' => 'string']),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'password' => Yii::t('app', 'Password'),
        ];
    }
}
