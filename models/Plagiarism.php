<?php

namespace app\models;

use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;

/**
 * This is the model class for table "plagiarisms".
 *
 * @property integer $id
 * @property string $token A 32-character hexadecimal string
 *  (e.g. an MD5 hash) used to prevent unauthorized users from
 *  viewing the result of random plagiarism checks by guessing
 *  their primary IDs.
 * @property integer $requesterID
 * @property string $taskIDs
 * @property string $userIDs
 * @property integer $semesterID
 * @property string $name
 * @property string $description
 * @property string $response
 * @property integer $ignoreThreshold
 * @property string $baseFileIDs
 *
 * @property-read Semester $semester
 * @property-read User $requester
 * @property-read PlagiarismBasefile[] $baseFiles The [[PlagiarismBasefile]] objects connected to this plagiarism check
 *
 * ToDo:
 *      -Rename to Plagiarism Validations.
 */
class Plagiarism extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    public const SCENARIO_UPDATE = 'update';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%plagiarisms}}';
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        // Only the name and description can be mass assigned when updating.
        $scenarios[self::SCENARIO_UPDATE] = ['name', 'description'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['requesterID', 'taskIDs', 'userIDs', 'semesterID', 'name', 'ignoreThreshold'], 'required'],
            [['requesterID', 'semesterID', 'ignoreThreshold'], 'integer'],
            [['token'], 'string', 'length' => 32],
            [['taskIDs', 'userIDs', 'baseFileIDs'], 'string', 'max' => 65535],
            [['response'], 'string', 'max' => 300],
            [['name'], 'string', 'max' => 30],
            [['ignoreThreshold'], 'integer', 'min' => 1, 'max' => 1000],
            ['ignoreThreshold', 'default', 'value' => 10],
            [['description'], 'string'],
            [
                ['semesterID'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Semester::class,
                'targetAttribute' => ['semesterID' => 'id']
            ],
            [
                ['requesterID'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['requesterID' => 'id']
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'token' => 'Authorization token',
            'requesterID' => 'Requester ID',
            'taskIDs' => 'Task IDs',
            'userIDs' => 'User IDs',
            'semesterID' => 'Semester ID',
            'name' => 'Name',
            'description' => 'Description',
            'response' => 'Response',
            'ignoreThreshold' => 'Ignore threshold',
            'baseFileIDs' => 'Base file IDs',
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'token' => new OAProperty(['type' => 'string']),
            'requesterID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'taskIDs' => new OAProperty(['ref' => '#/components/schemas/int_id_list']),
            'userIDs' => new OAProperty(['ref' => '#/components/schemas/int_id_list']),
            'semesterID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'name' => new OAProperty(['type' => 'string']),
            'description' => new OAProperty(['type' => 'string']),
            'response' => new OAProperty(['type' => 'string', 'deprecated' => 'true']),
            'url' => new OAProperty(['type' => 'string']),
            'ignoreThreshold' => new OAProperty(['type' => 'integer']),
            'baseFileIDs' => new OAProperty(['ref' => '#/components/schemas/int_id_list']),
        ];
    }

    public function getSemester(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Semester::class, ['id' => 'semesterID']);
    }

    public function getRequester(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'requesterID']);
    }

    /**
     * Get the [[PlagiarismBasefile]] objects connected to this plagiarism check.
     * @return \app\models\BaseFile[]
     */
    public function getBaseFiles(): array
    {
        return PlagiarismBasefile::findAll(explode(',', $this->baseFileIDs));
    }
}
