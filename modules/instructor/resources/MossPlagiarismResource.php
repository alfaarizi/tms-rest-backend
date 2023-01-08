<?php

namespace app\modules\instructor\resources;

use app\models\MossPlagiarism;
use yii\helpers\Url;

class MossPlagiarismResource extends MossPlagiarism implements ITypeSpecificPlagiarismResource
{
    public function fields(): array
    {
        return [
            'type',
            'ignoreThreshold',
        ];
    }

    public function extraFields()
    {
        return [];
    }

    public function getUrl(): ?string {
        $token = $this->plagiarism->token;
        if ($token) {
            return Url::to(['plagiarism-result/index', 'id' => $this->plagiarismId, 'token' => $token]);
        } else {
            return $this->response;
        }
    }

    public function getMainFileName(): string
    {
        return 'index.html';
    }
}
