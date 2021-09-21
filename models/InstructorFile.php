<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "instructorFiles".
 *
 * @property integer $id
 * @property string $name
 * @property string $uploadTime
 * @property integer $taskID
 * @property-read string $path
 *
 * @property Task $task
 */
class InstructorFile extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%instructor_files}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'path', 'taskID'], 'required'],
            [['uploadTime'], 'safe'],
            [['taskID'], 'integer'],
            [['name'], 'string', 'max' => 200],
            [
                ['taskID'],
                'exist',
                'skipOnError' => false,
                'targetClass' => Task::class,
                'targetAttribute' => ['taskID' => 'id']
            ],
            [
                ['name'],
                'unique',
                'targetAttribute' => ['name', 'taskID'],
                'message' => Yii::t('app', 'File with the same name already exists for this task')
            ],
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
            'path' => Yii::t('app', 'Path'),
            'uploadTime' => Yii::t('app', 'Upload Time'),
            'taskID' => Yii::t('app', 'Task ID'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        return unlink($this->path);
    }

    /**
     * @return string Absolute path.
     */
    public function getPath()
    {
        return Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/' . $this->taskID . '/' . $this->name;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'taskID']);
    }
}
