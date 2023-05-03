<?php

namespace app\models;

use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "plagiarisms_moss".
 *
 * @author Kristóf Károlyi <karolyikristof99@gmail.com>
 * @author Tamás J. Tóth <ft1r1l@inf.elte.hu>
 *
 * @property int $id
 * @property int $plagiarismId
 * @property int $ignoreThreshold
 * @property string|null $response
 *
 * @property-read Plagiarism $plagiarism
 */
class MossPlagiarism extends AbstractPlagiarism implements IOpenApiFieldTypes
{
    public const ID = 'moss';

    public function getType(): string
    {
        return MossPlagiarism::ID;
    }

    /** {@inheritdoc} */
    public static function tableName()
    {
        return '{{%plagiarisms_moss}}';
    }

    /** {@inheritdoc} */
    public function rules()
    {
        return [
            [['plagiarismId', 'ignoreThreshold'], 'required'],
            [['plagiarismId'], 'integer'],
            [['ignoreThreshold'], 'integer', 'min' => 1, 'max' => 1000],
            [['ignoreThreshold'], 'default', 'value' => 10],
            [['response'], 'string', 'max' => 300],
            [['plagiarismId'], 'unique'],
            [['plagiarismId'], 'exist', 'skipOnError' => true, 'targetClass' => Plagiarism::class, 'targetAttribute' => ['plagiarismId' => 'id']],
        ];
    }

    /** {@inheritdoc} */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'plagiarismId' => 'Plagiarism ID',
            'ignoreThreshold' => 'Ignore threshold',
            'response' => 'Response',
        ];
    }

    /**
     * Gets query for [[Plagiarism]].
     */
    public function getPlagiarism(): ActiveQuery
    {
        return $this->hasOne(Plagiarism::class, ['id' => 'plagiarismId']);
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'plagiarismId' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'ignoreThreshold' => new OAProperty(['type' => 'integer']),
            'response' => new OAProperty(['type' => 'string']),
            'type' => new OAProperty(['type' => 'string', 'enum' => new OAList([MossPlagiarism::ID])]),
        ];
    }
}
