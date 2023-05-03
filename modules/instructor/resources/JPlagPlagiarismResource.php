<?php

namespace app\modules\instructor\resources;

use app\models\JPlagPlagiarism;
use Yii;
use yii\helpers\Url;

class JPlagPlagiarismResource extends JPlagPlagiarism implements ITypeSpecificPlagiarismResource
{
    public function fields(): array
    {
        return [
            'type',
            'tune',
        ];
    }

    public function extraFields()
    {
        return [];
    }

    public function getUrl(): ?string
    {
        $token = $this->plagiarism->token;
        if ($token) {
            $zipUrl = Url::to(['plagiarism-result/index', 'id' => $this->plagiarismId, 'token' => $token], null);
            return Yii::$app->params['jplag']['report-viewer'] . '?file=' . urlencode($zipUrl);
        } else {
            return null;
        }
    }

    public function getMainFileName(): string
    {
        return 'result.zip';
    }
}
