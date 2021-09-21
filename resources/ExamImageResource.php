<?php

namespace app\resources;

use Yii;
use app\models\Model;
use yii\helpers\Url;

class ExamImageResource extends Model
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

    public $name;
    public $questionSetID;

    /**
     * ExamImageResource constructor.
     * @param $name
     * @param $questionSetID
     */
    public function __construct($name, $questionSetID)
    {
        $this->name = $name;
        $this->questionSetID = $questionSetID;
    }

    public function getUrl()
    {
        $base = Yii::$app->getUrlManager()->getBaseUrl();
        $url = Url::to(['/images/view-exam-image', 'id' => $this->questionSetID, 'filename' => $this->name], false);

        return str_replace($base, '', $url);
    }

    public function getFolderPath()
    {
        return  Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/examination/' . $this->questionSetID . '/';
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
