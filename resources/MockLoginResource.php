<?php

namespace app\resources;

use Yii;
use yii\base\Model;

/**
 * MockLoginForm is the model behind the development purpose login form.
 */
class MockLoginResource extends Model
{
    public $neptun;
    public $name;
    public $email;
    public $isStudent = true;
    public $isTeacher = false;
    public $isAdmin = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['neptun', 'name', 'email'], 'required'],
            ['email', 'email'],
            [['isStudent', 'isTeacher', 'isAdmin'], 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'neptun' => Yii::t('app', 'Neptun'),
            'name' => Yii::t('app', 'Name'),
            'email' => Yii::t('app', 'Email'),
            'isStudent' => Yii::t('app', 'Student access'),
            'isTeacher' => Yii::t('app', 'Teacher access'),
            'isAdmin' => Yii::t('app', 'Admin access'),
        ];
    }
}
