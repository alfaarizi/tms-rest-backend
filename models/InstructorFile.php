<?php

namespace app\models;

use app\behaviors\ISODateTimeBehavior;
use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\queries\InstructorFileQuery;
use Yii;

/**
 * This is the model class for table "instructorFiles".
 *
 * @property integer $id
 * @property string $name
 * @property string $uploadTime
 * @property integer $taskID
 * @property string $category
 * @property-read boolean $isAttachment
 * @property-read boolean $isTestFile
 * @property-read Task $task
 */
class InstructorFile extends File implements IOpenApiFieldTypes
{
    /**
     * Attachment category for files to be shared with students.
     */
    public const CATEGORY_ATTACHMENT = 'Attachment';
    /**
     * Test file category for files to be used in the automatic tester.
     */
    public const CATEGORY_TESTFILE = 'Test file';

    /**
     * Array of supported category types for an InstructorFile.
     */
    private const CATEGORY_TYPES = [
        self::CATEGORY_ATTACHMENT,
        self::CATEGORY_TESTFILE,
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%instructor_files}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => ISODateTimeBehavior::class,
                'attributes' => ['uploadTime']
            ]
        ];
    }

    /**
     * Get a 'map' of the supported category types and their language-specific translations.
     *
     * @param string $language the language code (e.g. `en-US`, `en`). If this is null, the current
     * [[\yii\base\Application::language|application language]] will be used.
     * @return array An associative array of category types and their language-specific translations.
     */
    public static function categoryMap($language = null)
    {
        $translations = array_map(function ($category) use ($language) {
            return Yii::t('app', $category, [], $language);
        }, self::CATEGORY_TYPES);

        return array_combine(self::CATEGORY_TYPES, $translations);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'path', 'taskID', 'category'], 'required'],
            [['uploadTime'], 'safe'],
            [['taskID'], 'integer'],
            [['name'], 'string', 'max' => 200],
            [['category'], 'in', 'range' => self::CATEGORY_TYPES],
            [
                ['taskID'],
                'exist',
                'skipOnError' => false,
                'targetClass' => Task::class,
                'targetAttribute' => ['taskID' => 'id']
            ],
            [
                ['name'],
                'unique',
                'targetAttribute' => ['name', 'taskID'],
                'message' => Yii::t('app', 'File with the same name already exists for this task')
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'path' => Yii::t('app', 'Path'),
            'uploadTime' => Yii::t('app', 'Upload Time'),
            'taskID' => Yii::t('app', 'Task ID'),
            'category' => Yii::t('app', 'Category'),
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'name' => new OAProperty(['type' => 'string']),
            'path' => new OAProperty(['type' => 'string']),
            'uploadTime' => new OAProperty(['type' => 'string']),
            'taskID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'category' => new OAProperty(['type' => 'string', 'enum' => new OAList(self::CATEGORY_TYPES)]),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getPath(): string
    {
        return Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/' . $this->taskID . '/' . $this->name;
    }

    public function getTask(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Task::class, ['id' => 'taskID']);
    }

    /**
     * Returns whether the instructor file is an attachment for the task.
     * @return bool
     */
    public function getIsAttachment()
    {
        return $this->category == self::CATEGORY_ATTACHMENT;
    }

    /**
     * Returns whether the instructor file is a test file the task.
     * @return bool
     */
    public function getIsTestFile()
    {
        return $this->category == self::CATEGORY_TESTFILE;
    }

    public function getTranslatedCategory()
    {
        return Yii::t('app', $this->category);
    }

    /**
     * {@inheritdoc}
     * @return InstructorFileQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new InstructorFileQuery(get_called_class());
    }
}
