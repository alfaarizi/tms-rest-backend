<?php

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "task_access_tokens".
 *
 * @property integer $id
 * @property string $accessToken
 * @property integer $taskId
 *
 * @property Task $task
 */
class TaskAccessTokens extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%task_access_tokens}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['accessToken', 'taskId'], 'required'],
            [['taskId'], 'integer'],
            [['accessToken',], 'string', 'max' => 255],
            [['taskId'], 'exist', 'skipOnError' => true, 'targetClass' => Task::class, 'targetAttribute' => ['taskId' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'accessToken' => 'Token',
            'TaskId' => 'Task ID',
        ];
    }

    /**
     * Gets query for [[Task]].
     *
     * @return ActiveQuery
     */
    public function getTask(): ActiveQuery
    {
        return $this->hasOne(Task::class, ['id' => 'taskId']);
    }
}
