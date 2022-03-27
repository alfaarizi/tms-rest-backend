<?php

namespace app\modules\instructor\resources;

use yii\helpers\Url;

class PlagiarismResource extends \app\models\Plagiarism
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'id',
            'semesterID',
            'name',
            'description',
            'response', // TODO deprecate and remove this implementation detail in favor of `url`
            'url',
            'ignoreThreshold'
        ];
    }

    /**
     * URL of the downloaded plagiarism result (can be embedded as
     * an `<iframe>`), or `null` if there’s nothing downloaded.
     * @return string|null
     */
    public function getUrl() {
        if ($this->token) {
            return Url::to(['plagiarism-result/index', 'id' => $this->id, 'token' => $this->token]);
        } else {
            return $this->response;
        }
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [];
    }
}
