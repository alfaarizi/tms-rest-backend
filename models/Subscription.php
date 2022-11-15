<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "subscriptions".
 *
 * @property integer $id
 * @property integer $semesterID
 * @property integer $groupID
 * @property integer $userID
 * @property integer $isAccepted
 * @property string $notes
 *
 * @property User $user
 * @property Group $group
 * @property Semester $semester
 */
class Subscription extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%subscriptions}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['semesterID', 'groupID', 'userID', 'isAccepted'], 'integer'],
            [['groupID', 'userID'], 'required'],
            [['isAccepted'], 'boolean'],
            [
                ['userID'],
                'unique',
                'targetAttribute' => ['semesterID', 'groupID', 'userID'],
                'message' => Yii::t('app', 'This user has been already added to this group.')
            ],
            [['notes'], 'string', 'max' => 500]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'semesterID' => Yii::t('app', 'Semester ID'),
            'groupID' => Yii::t('app', 'Group ID'),
            'userID' => Yii::t('app', 'User ID'),
            'isAccepted' => Yii::t('app', 'Is Accepted'),
            'notes' => Yii::t('app', 'Notes')
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
    public function getGroup()
    {
        return $this->hasOne(Group::class, ['id' => 'groupID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSemester()
    {
        return $this->hasOne(Semester::class, ['id' => 'semesterID']);
    }

    public function getNewSolutionCount()
    {
        return $this->user->getFiles()
            ->where(
                [
                    'isAccepted' => [
                        StudentFile::IS_ACCEPTED_UPLOADED,
                        StudentFile::IS_ACCEPTED_PASSED,
                        StudentFile::IS_ACCEPTED_FAILED,
                    ]
                ]
            )
            ->andWhere(
                [
                    'taskID' => array_map(
                        function ($o) {
                            return $o->id;
                        },
                        Task::findAll(['groupID' => $this->groupID])
                    )
                ]
            )->count();
    }
}
