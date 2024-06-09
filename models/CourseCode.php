<?php

namespace app\models;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;

/**
 * This is the model class for table "course codes".
 *
 * @property integer $id
 * @property integer $courseId
 * @property string $code
 */
class CourseCode extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%course_codes}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code'], 'required'],
            [['code'], 'string'],
            [['courseId'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'courseId' => Yii::t('app', 'Course ID'),
            'code' => Yii::t('app', 'Code')
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['type' => 'integer']),
            'courseId' => new OAProperty(['type' => 'integer']),
            'code' => new OAProperty(['type' => 'string'])
        ];
    }
}
