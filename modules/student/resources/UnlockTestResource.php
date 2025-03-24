<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;

/**
 * @property integer $id
 * @property string $password
 */
class UnlockTestResource extends \app\models\Model implements IOpenApiFieldTypes
{
    public ?int $id = null;
    public ?string $password = null;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['id'], 'number'],
            [['id', 'password'], 'required'],
            [['password'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        return  ['id', 'password'];
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'password' => new OAProperty(['type' => 'string']),
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
        ];
    }
}
