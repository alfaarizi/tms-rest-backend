<?php

namespace app\models;

use app\behaviors\ISODateTimeBehavior;
use app\components\GitManager;
use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\queries\TaskQuery;
use Yii;
use yii\helpers\FileHelper;

/**
 * This is the model class for table "tasks".
 *
 * @property integer $id
 * @property string $name
 * @property integer $semesterID
 * @property integer $groupID
 * @property string $category
 * @property resource $description
 * @property string $softDeadline
 * @property string $hardDeadline
 * @property string $available
 * @property integer $createrID
 * @property boolean $isVersionControlled
 * @property integer $autoTest
 * @property string $testOS
 * @property integer $showFullErrorMsg
 * @property string $imageName
 * @property string $compileInstructions
 * @property string $runInstructions
 * @property string $codeCompassCompileInstructions
 * @property string $codeCompassPackagesInstallInstructions
 * @property integer $canvasID
 * @property integer $port
 * @property string $appType
 * @property string $password
 * @property boolean $staticCodeAnalysis
 * @property string $staticCodeAnalyzerTool
 * @property string $staticCodeAnalyzerInstructions
 * @property string $codeCheckerCompileInstructions
 * @property string $codeCheckerToggles
 * @property string $codeCheckerSkipFile
 *
 * @property InstructorFile[] $instructorFiles
 * @property StudentFile[] $studentFiles
 * @property TestCase[] $testCases
 * @property Group $group
 * @property Semester $semester
 * @property User $creator
 *
 * @property-read string $timezone
 * @property-read boolean $passwordProtected
 * @property-read ?string $canvasUrl
 * @property-read string $localImageName
 * @property-read string $isLocalImage
 * @property-read string $dockerSocket
 */
class Task extends \yii\db\ActiveRecord implements IOpenApiFieldTypes
{
    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_UPDATE = 'update';
    public const MAX_NAME_LENGTH = 40;

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_CREATE] = [
            'name',
            'category',
            'description',
            'softDeadline',
            'hardDeadline',
            'available',
            'isVersionControlled',
            'groupID',
            'password',
        ];
        $scenarios[self::SCENARIO_UPDATE] = [
            'name',
            'category',
            'description',
            'softDeadline',
            'hardDeadline',
            'available',
            'password',
        ];

        return $scenarios;
    }

    /**
     * Category type for Canvas tasks.
     */
    public const CATEGORY_TYPE_CANVAS_TASKS = 'Canvas tasks';

    /**
     * Array of supported category types for a Task.
     */
    private const CATEGORY_TYPES = [
        'Smaller tasks',
        'Larger tasks',
        'Classwork tasks',
        'Exams',
        self::CATEGORY_TYPE_CANVAS_TASKS
    ];

    public const TEST_OS = [
        'linux',
        'windows',
    ];

    public const APP_TYPE_WEB = 'Web';
    public const APP_TYPE_CONSOLE = 'Console';

    //Supported application archetypes
    public const APP_TYPES = [
        self::APP_TYPE_CONSOLE,
        self::APP_TYPE_WEB
    ];

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => ISODateTimeBehavior::class,
                'attributes' => ['hardDeadline', 'softDeadline', 'available']
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tasks}}';
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
     * Get a 'map' of the supported operating systems for automated testing.
     *
     * @return array An associative array of OS kinds in an identity mapping (with captialized values).
     */
    public static function testOSMap()
    {
        $keys = array_filter(self::TEST_OS, function ($os) {
            return !empty(Yii::$app->params['evaluator'][$os]);
        });
        $values = array_map(function ($os) {
            return ucfirst($os);
        }, $keys);
        return array_combine($keys, $values);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'semesterID', 'groupID', 'hardDeadline', 'category'], 'required'],
            [['semesterID', 'groupID'], 'integer'],
            [['description'], 'string'],
            [['category'], 'in', 'range' => self::CATEGORY_TYPES],
            [['testOS'], 'in', 'range' => self::TEST_OS],
            [['isVersionControlled'], 'boolean'],
            [['softDeadline', 'available'], 'safe'],
            [['name'], 'string', 'max' => self::MAX_NAME_LENGTH],
            [
                ['groupID'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Group::class,
                'targetAttribute' => ['groupID' => 'id']
            ],
            [
                ['semesterID'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Semester::class,
                'targetAttribute' => ['semesterID' => 'id']
            ],
            [['autoTest', 'showFullErrorMsg'], 'boolean'],
            [['imageName', 'password'], 'string', 'max' => 255],
            [['compileInstructions', 'runInstructions'], 'string'],
            [['codeCompassPackagesInstallInstructions'], 'string', 'max' => 500],
            [['compileInstructions', 'runInstructions', 'codeCompassCompileInstructions'], 'string'],
            [['port'], 'integer', 'min' => 1, 'max' => 65353],
            [['appType'], 'string'],
            [['port'], 'required', 'when' => function ($model) {
                return $model->appType == Task::APP_TYPE_WEB;
            }],
            [['appType'], 'in', 'range' => self::APP_TYPES],
            [['appType'], 'required', 'when' => function ($model) {
                return $model->imageName != null;
            }],
            [
                ['staticCodeAnalyzerTool'],
                'required',
                'when' => function ($model) {
                    return $model->staticCodeAnalysis;
                }
            ],
            [
                ['staticCodeAnalyzerTool'],
                'in',
                'range' => array_merge(['codechecker'], array_keys(Yii::$app->params["evaluator"]["supportedStaticAnalyzerTools"])),
            ],
            [
                ['codeCheckerCompileInstructions'],
                'required',
                'when' => function ($model) {
                    return $model->staticCodeAnalysis && $model->staticCodeAnalyzerTool === 'codechecker';
                }
            ],
            [
                ['staticCodeAnalyzerInstructions'],
                'required',
                'when' => function ($model) {
                    return $model->staticCodeAnalysis && $model->staticCodeAnalyzerTool !== 'codechecker';
                }
            ]
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
            'semesterID' => Yii::t('app', 'Semester ID'),
            'groupID' => Yii::t('app', 'Group ID'),
            'category' => Yii::t('app', 'Category'),
            'description' => Yii::t('app', 'Description'),
            'softDeadline' => Yii::t('app', 'Soft deadline'),
            'hardDeadline' => Yii::t('app', 'Hard deadline'),
            'available' => Yii::t('app', 'Available'),
            'createrID' => Yii::t('app', 'Created By'),
            'isVersionControlled' => Yii::t('app', 'Version control'),
            'autoTest' => Yii::t('app', 'Automatic Testing'),
            'testOS' => Yii::t('app', 'Operating System'),
            'showFullErrorMsg' => Yii::t('app', 'Show The Full Error Message'),
            'imageName' => Yii::t('app', 'Docker Image'),
            'compileInstructions' => Yii::t('app', 'Compile Instructions'),
            'runInstructions' => Yii::t('app', 'Run Instructions'),
            'canvasID' => Yii::t('app', 'Canvas id'),
            'password' => Yii::t('app', 'Password'),
            'port' => Yii::t('app', 'Port'),
            'appType' => Yii::t('app', 'Application type'),
            'codeCompassCompileInstructions' => Yii::t('app', 'CodeCompass Compile Instructions'),
            'codeCompassPackagesInstallInstructions' => Yii::t('app', 'CodeCompass Packages'),
            'staticCodeAnalysis' => Yii::t('app', 'Static Code Analysis'),
            'staticCodeAnalyzerTool' => Yii::t('app', 'Static Code Analyzer Tool'),
            'staticCodeAnalyzerInstructions' => Yii::t('app', 'Static Code Analyzer Instructions'),
            'codeCheckerCompileInstructions' => Yii::t('app', 'CodeChecker Compiler Instructions'),
            'codeCheckerToggles' => Yii::t('app', 'CodeChecker Toggles'),
            'codeCheckerSkipFile' => Yii::t('app', 'CodeChecker Skipfile'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->createrID = Yii::$app->user->id;
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        if (is_null($this->createrID)) {
            return false;
        }
        if (!is_null($this->imageName) && strpos($this->imageName, ":") === false) {
            $this->imageName .= ":latest";
        }
        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        FileHelper::removeDirectory(Yii::getAlias("@appdata/uploadedfiles/") . $this->id . '/');
        return true;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $this->compileInstructions = str_replace("\r\n", "\n", $this->compileInstructions);
        $this->runInstructions = str_replace("\r\n", "\n", $this->runInstructions);

        // Remove unverified status from files after removing password
        if (!$insert && array_key_exists('password', $this->dirtyAttributes) && empty($this->password)) {
            StudentFile::updateAll(
                ['verified' => true],
                ['=', 'taskID', $this->id],
            );
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            $directoryPath = Yii::getAlias("@appdata/uploadedfiles/") . $this->id . '/';
            FileHelper::createDirectory($directoryPath, 0755, true);

            // Create git repositories if a new task has been created and the task is version controlled
            if (Yii::$app->params['versionControl']['enabled'] && $this->isVersionControlled) {
                GitManager::createTaskLevelRepository($this->id);
                // Create remote repository for everybody in the group
                foreach ($this->group->subscriptions as $subscription) {
                    GitManager::createUserRepository($this, $subscription->user);
                }
            }
        }
    }

    /**
     * Generates the local image name of this task.
     * @return string
     */
    public function getLocalImageName()
    {
        // Prefixing.
        return "tms/task_{$this->id}:latest";
    }

    /**
     * Checks if an image is locally built from Dockerfile.
     * @return bool <code>true</code> if image built locally, otherwise <code>false</code>.
     */
    public function getIsLocalImage(): bool
    {
        return $this->localImageName == $this->imageName;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstructorFiles()
    {
        return $this->hasMany(InstructorFile::class, ['taskID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStudentFiles()
    {
        return $this->hasMany(StudentFile::class, ['taskID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTestCases()
    {
        return $this->hasMany(TestCase::class, ['taskID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(Group::class, ['id' => 'groupID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSemester()
    {
        return $this->hasOne(Semester::class, ['id' => 'semesterID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(User::class, ['id' => 'createrID']);
    }

    /**
     * {@inheritdoc}
     * @return TaskQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new TaskQuery(get_called_class());
    }

    /**
     * List categories for a given groupID and semesterID
     * @param Group $group
     * @return Task[]|array
     */
    public static function listCategories($group)
    {
        return Task::find()->select(['category'])->distinct()
            ->where([
                    'groupID' => $group->id,
                    'semesterID' => $group->semesterID,
                ])
            ->orderBy('category')
            ->asArray()->all();
    }

    public function getTranslatedCategory()
    {
        return Yii::t('app', $this->category);
    }

    public function getCreatorName()
    {
        return $this->creator->name;
    }

    public function getCanvasUrl(): ?string
    {
        $canvasParams = Yii::$app->params['canvas'];
        return ($canvasParams['enabled'] && $this->category === 'Canvas tasks')
            ? rtrim($canvasParams['url'], '/') . '/courses/' . $this->group->canvasCourseID . '/assignments/' . $this->canvasID
            : null;
    }

    public function getPasswordProtected()
    {
        return !empty($this->password);
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'name' => new OAProperty(['type' => 'string']),
            'category' => new OAProperty(['type' => 'string', 'enum' => new OAList(self::CATEGORY_TYPES)]),
            'translatedCategory' => new OAProperty(['type' => 'string']),
            'description' => new OAProperty(['type' => 'string']),
            'softDeadline' => new OAProperty(['type' => 'string', 'example' => '2022-01-01T23:59:00+00:00']),
            'hardDeadline' => new OAProperty(['type' => 'string', 'example' => '2022-01-01T23:59:00+00:00']),
            'available' => new OAProperty(['type' => 'string', 'example' => '2022-01-01T23:59:00+00:00']),
            'autoTest' => new OAProperty(['type' => 'integer']),
            'isVersionControlled' => new OAProperty(['type' => 'integer']),
            'groupID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'semesterID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'creatorName' => new OAProperty(['type' => 'string']),
            'testOS' => new OAProperty(['type' => 'string', 'enum' => new OAList(self::TEST_OS)]),
            'showFullErrorMsg' => new OAProperty(['type' => 'integer']),
            'imageName' => new OAProperty(['type' => 'string']),
            'compileInstructions' => new OAProperty(['type' => 'string']),
            'runInstructions' => new OAProperty(['type' => 'string']),
            'canvasUrl' => new OAProperty(['type' => 'string', 'nullable' => 'true']),
            'codeCompassCompileInstructions' => new OAProperty(['type' => 'string']),
            'codeCompassPackagesInstallInstructions' => new OAProperty(['type' => 'string']),
            'passwordProtected' => new OAProperty(['type' => 'boolean']),
            'password' => new OAProperty(['type' => 'string']),
            'port' => new OAProperty(['type' => 'integer']),
            'appType' => new OAProperty(['type' => 'string', 'enum' => new OAList(self::APP_TYPES)]),
            'staticCodeAnalysis' => new OAProperty(['type' => 'boolean']),
            'staticCodeAnalyzerTool' => new OAProperty(['type' => 'string']),
            'staticCodeAnalyzerInstructions' => new OAProperty(['type' => 'string']),
            'codeCheckerSkipFile' => new OAProperty(['type' => 'string']),
            'codeCheckerCompileInstructions' => new OAProperty(['type' => 'string']),
            'codeCheckerToggles' => new OAProperty(['type' => 'string']),
        ];
    }
}
