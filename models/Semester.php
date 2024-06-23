<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "semesters".
 *
 * @property integer $id
 * @property string $name
 * @property boolean $actual
 *
 * @property Subscription[] $subscriptions
 * @property Task[] $tasks
 */
class Semester extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%semesters}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'actual'], 'required'],
            [['actual'], 'boolean'],
            [['name'], 'string', 'max' => 10],
            [['name'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'semester' => Yii::t('app', 'Name'),
            'actual' => Yii::t('app', 'Actual'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubscriptions()
    {
        return $this->hasMany(Subscription::class, ['semesterID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTasks()
    {
        return $this->hasMany(Task::class, ['semesterid' => 'id']);
    }

    public static function getActualID()
    {
        return Semester::findOne(['actual' => 1])->id;
    }

    /**
     * This function calculates the next semester.
     */
    public static function calculateNextSemesterName()
    {
        $month = intval(date('n'));
        // August -> December: fall semester
        if ($month >= 8) {
            $nextSemestersName = sprintf(
                '%s/%s/2',
                date('Y'),
                date('y', strtotime('+1 year'))
            );
        } elseif ($month == 1) {
            // January: fall semester
            $nextSemestersName = sprintf(
                '%s/%s/2',
                date('Y', strtotime('-1 year')),
                date('y')
            );
        } else {
            // February -> July: spring semester
            $nextSemestersName = sprintf(
                '%s/%s/1',
                date('Y'),
                date('y', strtotime('+1 year'))
            );
        }
        return $nextSemestersName;
    }
}
