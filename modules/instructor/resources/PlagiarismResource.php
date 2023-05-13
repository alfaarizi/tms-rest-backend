<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use app\models\AbstractPlagiarism;
use app\models\JPlagPlagiarism;
use app\models\MossPlagiarism;
use yii\base\ErrorException;
use yii\db\ActiveQuery;

/**
 * @property-read MossPlagiarismResource|null $moss
 * @property-read JPlagPlagiarismResource|null $jplag
 * @property-read string|null $url URL of the downloaded plagiarism result
 *  (can be embedded as an `<iframe>`), or `null` if there’s nothing downloaded.
 * @property-read AbstractPlagiarism&ITypeSpecificPlagiarismResource $typeSpecificData
 */
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
            'url',
            'typeSpecificData',
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [];
    }

    public function getMoss(): ActiveQuery
    {
        return $this->hasOne(MossPlagiarismResource::class, ['plagiarismId' => 'id']);
    }

    public function getJplag(): ActiveQuery
    {
        return $this->hasOne(JPlagPlagiarismResource::class, ['plagiarismId' => 'id']);
    }

    public function getUrl(): ?string
    {
        return $this->getTypeSpecificData()->getUrl();
    }

    /** @return AbstractPlagiarism&ITypeSpecificPlagiarismResource */
    public function getTypeSpecificData(): AbstractPlagiarism
    {
        switch ($this->type) {
            case MossPlagiarism::ID:
                return $this->moss;
            case JPlagPlagiarism::ID:
                return $this->jplag;
            default:
                throw new ErrorException('Not existing plagiarism type.');
        }
    }

    public function fieldTypes(): array
    {
        $fieldTypes = parent::fieldTypes();
        $fieldTypes['url'] = new OAProperty(['type' => 'string', 'nullable' => 'true']);
        $fieldTypes['typeSpecificData'] = new OAProperty([
            'ref' => [
                '#/components/schemas/Instructor_MossPlagiarismResource_Read',
                '#/components/schemas/Instructor_JPlagPlagiarismResource_Read',
            ]
        ]);
        return $fieldTypes;
    }
}
