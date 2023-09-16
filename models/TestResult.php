<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "testResults".
 *
 * @property integer $id
 * @property integer $testCaseID
 * @property integer $studentFileID
 * @property boolean $isPassed
 * @property string $errorMsg
 * @property-read string $safeErrorMsg
 *
 * @property StudentFile $studentFile
 * @property TestCase $testCase
 */

class TestResult extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%test_results}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['testCaseID', 'studentFileID', 'isPassed'], 'required'],
            [['testCaseID', 'studentFileID'], 'integer'],
            [['isPassed'], 'boolean'],
            [['errorMsg'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'testCaseID' => Yii::t('app', 'TestCase ID'),
            'studentFileID' => Yii::t('app', 'StudentFile ID'),
            'isPassed' => Yii::t('app', 'Passed'),
            'errorMsg' => Yii::t('app', 'Error Message')
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStudentFile()
    {
        return $this->hasOne(StudentFile::class, ['id' => 'studentFileID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTestCase()
    {
        return $this->hasOne(TestCase::class, ['id' => 'testCaseID']);
    }

    /**
     * Replaces full error message with a generic one if showFullErrorMsg is disabled
     */
    public function getSafeErrorMsg(): ?string
    {
        if ($this->studentFile->task->showFullErrorMsg) {
            return $this->errorMsg;
        }

        if (!$this->isPassed) {
            return Yii::t('app', 'Your solution failed the test');
        }

        return '';
    }
}
