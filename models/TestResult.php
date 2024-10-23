<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "testResults".
 *
 * @property integer $id
 * @property integer $testCaseID
 * @property integer $submissionID
 * @property boolean $isPassed
 * @property string $errorMsg
 * @property-read string $safeErrorMsg
 *
 * @property Submission $submission
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
            [['testCaseID', 'submissionID', 'isPassed'], 'required'],
            [['testCaseID', 'submissionID'], 'integer'],
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
            'submissionID' => Yii::t('app', 'Submission ID'),
            'isPassed' => Yii::t('app', 'Passed'),
            'errorMsg' => Yii::t('app', 'Error Message')
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubmission()
    {
        return $this->hasOne(Submission::class, ['id' => 'submissionID']);
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
        if ($this->submission->task->showFullErrorMsg) {
            return $this->errorMsg;
        }

        if (!$this->isPassed) {
            return Yii::t('app', 'Your solution failed the test');
        }

        return '';
    }
}
