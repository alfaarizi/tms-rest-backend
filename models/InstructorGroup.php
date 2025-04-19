<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "instuctorgroups".
 *
 * @property int $userID
 * @property int|null $groupID
 *
 * @property User $user
 * @property Group $group
 */
class InstructorGroup extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%instructor_groups}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['userID', 'groupID'], 'integer'],
            [['userID', 'groupID'], 'required'],
            [
                ['userID'],
                'unique',
                'targetAttribute' => ['groupID', 'userID'],
                'message' =>  Yii::t('app', 'This user has been already added to this group.')
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
            'groupID' => Yii::t('app', 'Group ID'),
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
}
