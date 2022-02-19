<?php

namespace app\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;
use yii\base\Model;

/**
 * MockLoginForm is the model behind the development purpose login form.
 */
class MockLoginResource extends Model implements IOpenApiFieldTypes
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

    public function fieldTypes(): array
    {
        return [
            'neptun' => new OAProperty(['type' => 'string', 'example' => 'batman']),
            'name' => new OAProperty(['type' => 'string', 'example' => 'Bruce Wayne']),
            'email' => new OAProperty(['type' => 'string', 'example' => 'batman@nanana.hu']),
            'isStudent' => new OAProperty(['type' => 'boolean', 'example' => 'true']),
            'isTeacher' => new OAProperty(['type' => 'boolean', 'example' => 'true']),
            'isAdmin' => new OAProperty(['type' => 'boolean', 'example' => 'true']),
        ];
    }
}
