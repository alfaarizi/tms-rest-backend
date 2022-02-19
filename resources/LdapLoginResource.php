<?php

namespace app\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;
use yii\base\Model;

/**
 * LoginResource is the model behind the LDAP login form.
 */
class LdapLoginResource extends Model implements IOpenApiFieldTypes
{
    public $username;
    public $password;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
        ];
    }

    /**
     * @return string[]
     */
    public function fields()
    {
        return ['username', 'password'];
    }

    /**
     * @return array
     */
    public function extraFields()
    {
        return [];
    }

    public function attributeLabels()
    {
        return [
            'username' => Yii::t('app', 'Username'),
            'password' => Yii::t('app', 'Password'),
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'username' => new OAProperty(['type' => 'string']),
            'password' => new OAProperty(['type' => 'string']),
        ];
    }
}
