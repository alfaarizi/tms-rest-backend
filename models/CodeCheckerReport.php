<?php

namespace app\models;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use yii\db\ActiveQuery;
use yii\helpers\Url;
use Yii;

/**
 * This is the model class for table "codechecker_reports".
 *
 * @property int $id
 * @property int $resultID
 * @property string $reportHash
 * @property string|null $filePath
 * @property int $line
 * @property int $column
 * @property string $checkerName
 * @property string $analyzerName
 * @property string $severity
 * @property string $category
 * @property string|null $message
 * @property string $plistFileName
 *
 * @property-read CodeCheckerResult $result
 *
 * @property-read string $viewerLink
 */
class CodeCheckerReport extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%codechecker_reports}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['resultID', 'reportHash', 'line', 'column', 'checkerName', 'analyzerName', 'severity', 'category'], 'required'],
            [['line', 'column'], 'integer'],
            [['message'], 'string'],
            [['resultID', 'reportHash', 'filePath', 'checkerName', 'analyzerName', 'severity', 'category'], 'string', 'max' => 255],
            [['resultID'], 'exist', 'skipOnError' => true, 'targetClass' => CodeCheckerResult::class, 'targetAttribute' => ['resultID' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'resultID' => Yii::t('app', 'Result ID'),
            'reportHash' => Yii::t('app', 'Report Hash'),
            'filePath' => Yii::t('app', 'File Path'),
            'line' => Yii::t('app', 'Line'),
            'column' => Yii::t('app', 'Column'),
            'checkerName' => Yii::t('app', 'Checker Name'),
            'analyzerName' => Yii::t('app', 'Analyzer Name'),
            'severity' => Yii::t('app', 'Severity'),
            'translatedSeverity' => Yii::t('app', 'Translated Severity'),
            'category' => Yii::t('app', 'Category'),
            'message' => Yii::t('app', 'Message'),
        ];
    }

    /**
     * Gets query for [[Result]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getResult(): ActiveQuery
    {
        return $this->hasOne(CodeCheckerResult::class, ['id' => 'resultID']);
    }

    public function getTranslatedSeverity(): string
    {
        return Yii::t('app', $this->severity);
    }

    public function getViewerLink(): string
    {
        return Url::to([
            '/code-checker-html-reports/view',
            'id' => $this->resultID,
            'token' => $this->result->token,
            'fileName' => "$this->plistFileName.html",
            '#' => "reportHash=$this->reportHash"
        ]);
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'resultID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'reportHash' => new OAProperty(['type' => 'string']),
            'filePath' => new OAProperty(['type' => 'string']),
            'line' => new OAProperty(['type' => 'number']),
            'column' => new OAProperty(['type' => 'number']),
            'checkerName' => new OAProperty(['type' => 'string']),
            'analyzerName' => new OAProperty(['type' => 'string']),
            'severity' => new OAProperty(['type' => 'string']),
            'translatedSeverity' => new OAProperty(['type' => 'string']),
            'category' => new OAProperty(['type' => 'string']),
            'message' => new OAProperty(['type' => 'string']),
            'viewerLink' => new OAProperty(['type' => 'string']),
            'plistFileName' => new OAProperty(['type' => 'string']),
        ];
    }
}
