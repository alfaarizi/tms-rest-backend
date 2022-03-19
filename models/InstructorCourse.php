<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "instuctorcourses".
 *
 * @property int $userID
 * @property int $courseID
 *
 * @property User $user
 * @property Course $course
 */
class InstructorCourse extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%instructor_courses}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['userID', 'courseID'], 'integer'],
            [['userID', 'courseID'], 'required'],
            [
                ['userID'],
                'unique',
                'targetAttribute' => ['courseID', 'userID'],
                'message' => Yii::t('app', 'The combination of  Course ID and User ID has already been taken.')
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'userID' => Yii::t('app', 'User ID'),
            'courseID' => Yii::t('app', 'Course ID'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourse()
    {
        return $this->hasOne(Course::class, ['id' => 'courseID']);
    }
}
