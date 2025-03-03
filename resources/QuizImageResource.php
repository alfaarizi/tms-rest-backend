<?php

namespace app\resources;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes as IOpenApiFieldTypesAlias;
use Yii;
use app\models\Model;
use yii\helpers\Url;

class QuizImageResource extends Model implements IOpenApiFieldTypesAlias
{
    public function fields()
    {
        return [
            'name',
            'url',
            'size'
        ];
    }

    public function extraFields()
    {
        return [];
    }

    public function fieldTypes(): array
    {
        return [
            'name' => new OAProperty(['type' => 'string']),
            'url' => new OAProperty(['type' => 'string']),
            'size' => new OAProperty(['type' => 'integer']),
        ];
    }

    public $name;
    public $questionSetID;

    public function getUrl()
    {
        $base = Yii::$app->getUrlManager()->getBaseUrl();
        $url = Url::to(['/images/view-quiz-image', 'id' => $this->questionSetID, 'filename' => $this->name], false);

        return str_replace($base, '', $url);
    }

    public function getFolderPath()
    {
        return  Yii::getAlias("@appdata/uploadedfiles/examination/") . $this->questionSetID . '/';
    }

    public function getFilePath()
    {
        return $this->getFolderPath() . $this->name;
    }

    public function getSize()
    {
        return filesize($this->getFilePath());
    }

    public function getUploadDate()
    {
        return filemtime($this->getFilePath());
    }
}
