<?php

namespace app\resources;

use Yii;
use yii\base\Model;

/**
 * LoginResource is the model behind the LDAP login form.
 */
class LdapLoginResource extends Model
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

    public function attributeLabels()
    {
        return [
            'username' => Yii::t('app', 'Username'),
            'password' => Yii::t('app', 'Password'),
        ];
    }
}
