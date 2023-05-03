<?php

namespace app\models;

use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "plagiarisms_jplag".
 *
 * @author Kristóf Károlyi <karolyikristof99@gmail.com>
 * @author Tamás J. Tóth <ft1r1l@inf.elte.hu>
 *
 * @property int $id
 * @property int $plagiarismId
 * @property int $tune
 * @property string $ignoreFiles
 *
 * @property-read Plagiarism $plagiarism
 */
class JPlagPlagiarism extends AbstractPlagiarism implements IOpenApiFieldTypes
{
    public const ID = 'jplag';

    public function getType(): string
    {
        return JPlagPlagiarism::ID;
    }

    /** {@inheritdoc} */
    public static function tableName()
    {
        return '{{%plagiarisms_jplag}}';
    }

    /** {@inheritdoc} */
    public function rules()
    {
        return [
            [['plagiarismId'], 'required'],
            [['plagiarismId', 'tune'], 'integer'],
            [['ignoreFiles'], 'string'],
            [['ignoreFiles'], 'match', 'pattern' => '~^[^\\\\/]+$~'],
            [['ignoreFiles'], 'default', 'value' => ''],
            [['tune'], 'default', 'value' => 0],
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
            'ignoreFiles' => 'Ignore Files',
            'tune' => 'Tune',
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
            'ignoreFiles' => new OAProperty(['type' => 'string']),
            'tune' => new OAProperty(['type' => 'integer']),
            'type' => new OAProperty(['type' => 'string', 'enum' => new OAList([JPlagPlagiarism::ID])]),
        ];
    }
}
