<?php

namespace app\models;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;

/**
 * This is the model class for table "courses".
 *
 * @property integer $id
 * @property string $name
 * @property string $code
 *
 * @property InstructorCourse[] $instructorCourses
 * @property User[] $lecturers
 */
class Course extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%courses}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 100],
            [['code'], 'string', 'max' => 15]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'code' => Yii::t('app', 'Code')
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['type' => 'integer']),
            'name' => new OAProperty(['type' => 'string']),
            'code' => new OAProperty(['type' => 'string']),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstructorCourses()
    {
        return $this->hasMany(InstructorCourse::class, ['courseID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLecturers()
    {
        return $this->hasMany(User::class, ['id' => 'userID'])
            ->viaTable('{{%instructor_courses}}', ['courseID' => 'id']);
    }
}
