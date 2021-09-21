<?php

namespace app\models;

use Yii;
use yii\helpers\StringHelper;

/**
 * This is the model class for table "studentFiles".
 *
 * @property integer $id
 * @property string $name
 * @property-read string $path
 * @property string $uploadTime
 * @property integer $taskID
 * @property integer $uploaderID
 * @property string $isAccepted
 * @property boolean $isVersionControlled
 * @property float $grade
 * @property string $notes
 * @property integer $graderID
 * @property string $errorMsg
 * @property integer $canvasID
 *
 * @property Task $task
 * @property User $uploader
 * @property User $grader
 */
class StudentFile extends \yii\db\ActiveRecord
{
    public const SCENARIO_GRADE = 'grade';

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_GRADE] = ['isAccepted', 'grade', 'notes'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%student_files}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'path', 'taskID', 'uploaderID', 'isAccepted'], 'required'],
            [['uploadTime'], 'safe'],
            [['taskID', 'uploaderID', 'graderID'], 'integer'],
            [['isAccepted'], 'string'],
            [['name'], 'string', 'max' => 200],
            [['grade'], 'number'],
            [['notes'], 'string'],
            [['isVersionControlled'], 'boolean'],
            [['errorMsg'], 'string'],
            [['isAccepted'], 'in', 'range' => ['Accepted', 'Rejected', 'Late Submission', 'Passed', 'Failed'], 'on' => self::SCENARIO_GRADE]
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
            'uploaderID' => Yii::t('app', 'Uploader ID'),
            'isAccepted' => Yii::t('app', 'Is Accepted'),
            'isVersionControlled' => Yii::t('app', 'Version control'),
            'grade' => Yii::t('app', 'Grade'),
            'notes' => Yii::t('app', 'Notes'),
            'graderID' => Yii::t('app', 'Graded By'),
            'errorMsg' => Yii::t('app', 'Error Message'),
            'canvasID' => Yii::t('app', 'Canvas id')
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
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $this->errorMsg = StringHelper::truncate($this->errorMsg, 65000);
        return true;
    }

    /**
     * @return string Absolute path.
     */
    public function getPath()
    {
        return Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/' .
            $this->taskID . '/' . strtolower($this->uploader->neptun) . '/' .
            $this->name;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'taskID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUploader()
    {
        return $this->hasOne(User::class, ['id' => 'uploaderID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGrader()
    {
        return $this->hasOne(User::className(), ['id' => 'graderID']);
    }

    public function getTranslatedIsAccepted()
    {
        return Yii::t('app', $this->isAccepted);
    }
}
