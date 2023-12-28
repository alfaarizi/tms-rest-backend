<?php

namespace app\models;

use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\components\plagiarism\AbstractPlagiarismFinder;
use app\components\plagiarism\JPlagPlagiarismFinder;
use app\components\plagiarism\MossPlagiarismFinder;
use yii\db\ActiveQuery;
use yii\helpers\FileHelper;

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
 * @property string $baseFileIDs
 * @property string|null $generateTime The time the plagiarism check was created (not executed; may be missing for older checks)
 * @property string $type The service used for this plagiarism check (`moss` or `jplag`)
 *
 * @property-read Semester $semester
 * @property-read User $requester
 * @property-read MossPlagiarism|null $moss The object containing Moss-specific configuration of the plagiarism check
 *  (if it is a Moss check)
 * @property-read JPlagPlagiarism|null $jplag The object containing JPlag-specific configuration of the plagiarism check
 *  (if it is a JPlag check)
 * @property-read StudentFile[] $studentFiles The [[StudentFile]] objects connected to this plagiarism check
 * @property-read bool $hasBaseFiles Whether there are any [[PlagiarismBasefile]] objects connected to this plagiarism check
 * @property-read PlagiarismBasefile[] $baseFiles The [[PlagiarismBasefile]] objects connected to this plagiarism check
 *
 * ToDo:
 *      -Rename to Plagiarism Validations.
 */
class Plagiarism extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    public const SCENARIO_UPDATE = 'update';
    public const POSSIBLE_TYPES = [MossPlagiarism::ID, JPlagPlagiarism::ID];

    /**
     * Get available plagiarism types.
     * @return string[] Internal names of the available types
     */
    public static function getAvailableTypes(): array
    {
        $types = [];
        if (MossPlagiarismFinder::isEnabled()) {
            $types[] = MossPlagiarism::ID;
        }
        if (JPlagPlagiarismFinder::isEnabled()) {
            $types[] = JPlagPlagiarism::ID;
        }
        return $types;
    }

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
            [['requesterID', 'taskIDs', 'userIDs', 'semesterID', 'name', 'type'], 'required'],
            [['requesterID', 'semesterID'], 'integer'],
            [['token'], 'string', 'length' => 32],
            [['taskIDs', 'userIDs', 'baseFileIDs'], 'string', 'max' => 65535],
            [['name'], 'string', 'max' => 30],
            [['description'], 'string'],
            [['type'], 'in', 'range' => Plagiarism::POSSIBLE_TYPES],
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
            'baseFileIDs' => 'Base file IDs',
            'type' => 'Type',
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
            'baseFileIDs' => new OAProperty(['ref' => '#/components/schemas/int_id_list']),
            'type' => new OAProperty(['type' => 'string', 'enum' => new OAList(Plagiarism::POSSIBLE_TYPES)]),
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

    public function getJplag(): ActiveQuery
    {
        return $this->hasOne(JPlagPlagiarism::class, ['plagiarismId' => 'id']);
    }

    public function getMoss(): ActiveQuery
    {
        return $this->hasOne(MossPlagiarism::class, ['plagiarismId' => 'id']);
    }

    public function getStudentFiles(): ActiveQuery
    {
        $query = StudentFile::find()->where([
            'uploaderID' => explode(',', $this->userIDs),
            'taskID' => explode(',', $this->taskIDs),
        ]);
        $query->multiple = true;
        return $query;
    }

    public function getHasBaseFiles(): bool
    {
        return (bool)$this->baseFileIDs;
    }

    /**
     * Get the [[PlagiarismBasefile]] objects connected to this plagiarism check.
     * @return PlagiarismBasefile[]
     */
    public function getBaseFiles(): array
    {
        return $this->hasBaseFiles ? PlagiarismBasefile::findAll(explode(',', $this->baseFileIDs)) : [];
    }

    /** {@inheritdoc} */
    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        $resultPath = AbstractPlagiarismFinder::getResultDirectory($this->id);
        if (file_exists($resultPath)) {
            FileHelper::removeDirectory($resultPath);
        }

        return true;
    }
}
