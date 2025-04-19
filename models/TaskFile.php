<?php

namespace app\models;

use app\behaviors\ISODateTimeBehavior;
use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\queries\TaskFileQuery;
use Yii;

/**
 * This is the model class for table "taskFiles".
 *
 * @property integer|null $id
 * @property string $name
 * @property string $uploadTime
 * @property integer|null $taskID
 * @property string $category
 * @property-read boolean $isAttachment
 * @property-read boolean $isTestFile
 * @property-read Task $task
 */
class TaskFile extends File implements IOpenApiFieldTypes
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
     * Test file category for files of test suite definitions for web tasks
     */
    public const CATEGORY_WEB_TEST_SUITE = 'Web test suite';

    /**
     * Array of supported category types for an TaskFile.
     */
    private const CATEGORY_TYPES = [
        self::CATEGORY_ATTACHMENT,
        self::CATEGORY_TESTFILE,
        self::CATEGORY_WEB_TEST_SUITE
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%task_files}}';
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
        return Yii::getAlias("@appdata/uploadedfiles/") . $this->taskID . '/' . $this->name;
    }

    public function getTask(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Task::class, ['id' => 'taskID']);
    }

    /**
     * Returns whether the task file is an attachment for the task.
     * @return bool
     */
    public function getIsAttachment()
    {
        return $this->category == self::CATEGORY_ATTACHMENT;
    }

    /**
     * Returns whether the task file is a test file the task.
     * @return bool
     */
    public function getIsTestFile()
    {
        return $this->category == self::CATEGORY_TESTFILE;
    }

    /**
     * Returns whether the task file is a web test suite.
     * @return bool
     */
    public function getIsWebTestSuite(): bool
    {
        return $this->category == self::CATEGORY_WEB_TEST_SUITE;
    }

    public function getTranslatedCategory()
    {
        return Yii::t('app', $this->category);
    }

    /**
     * {@inheritdoc}
     * @return TaskFileQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new TaskFileQuery(get_called_class());
    }
}
