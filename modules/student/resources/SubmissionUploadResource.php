<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\components\StructuralRequirementChecker;
use app\models\StructuralRequirement;
use Yii;
use yii\web\UploadedFile;

/**
 * Class SubmissionUploadResource
 * @property int $taskID
 * @property UploadedFile $file
 */
class SubmissionUploadResource extends \yii\base\Model implements IOpenApiFieldTypes
{
    public $taskID;
    public $file;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['taskID', 'file'], 'required'],
            [['taskID'], 'integer'],
            [['taskID'], 'checkIfTaskExists'],
            [['file'], 'file', 'skipOnEmpty' => false, 'extensions' => 'zip', 'maxSize' => 1024 * 1024 * 10],
            [['file'], 'validateFile'],
        ];
    }

    public function checkIfTaskExists()
    {
        $task = TaskResource::findOne($this->taskID);
        if (is_null($task)) {
            $this->addError('taskID', Yii::t('app', 'Task not found for the given taskID'));
        }
    }

    public function validateFile()
    {
        $structuralRequirements = StructuralRequirement::find()
            ->where(['taskID' => $this->taskID])
            ->all();
        $fileNames = [];
        $file = $this->file;
        $zip = new \ZipArchive();
        if ($zip->open($file->tempName) === true) {
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $filename = $zip->getNameIndex($i);
                $fileNames[] = $filename;
                if (strpos($filename, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR) !== false) {
                    $this->addError('file', Yii::t('app', 'The uploaded archive should NOT contain git repository.'));
                }

                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (in_array($extension, ['dll', 'exe'])) {
                    $this->addError('file', Yii::t('app', 'The uploaded archive should NOT contain binaries.'));
                }
            }

            $structuralRequirementResult = StructuralRequirementChecker::validatePaths(
                $structuralRequirements,
                $fileNames
            );

            foreach ($structuralRequirementResult['includeErrors'] as $error) {
                $this->addError('file', $error);
            }

            foreach ($structuralRequirementResult['excludeErrors'] as $error) {
                $this->addError('file', $error);
            }
        } else {
            $this->addError('file', Yii::t('app', 'The uploaded archive is corrupted, please retry!'));
        }
    }

    public function fieldTypes(): array
    {
        return [
            'taskID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'file' => new OAProperty(['type' => 'string', 'format' => 'binary'])
        ];
    }
}
