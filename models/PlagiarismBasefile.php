<?php

namespace app\models;

use app\behaviors\ISODateTimeBehavior;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use Yii;

/**
 * This class represents a “plagiarism basefile”, i.e. a file uploaded to be exempted from plagiarism checks.
 *
 * For example, instructor-provided files can be exempted to avoid false alarms when students legitimately use them.
 * It corresponds to the database table `base_files`.
 *
 * @property int $id
 * @property string $name
 * @property string $lastUpdateTime
 * @property int $courseID
 * @property int $uploaderID
 * @property-read Course $course
 * @property-read Group[] $groups
 * @property-read User $user
 */
class PlagiarismBasefile extends File implements IOpenApiFieldTypes
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%plagiarism_basefiles}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => ISODateTimeBehavior::class,
                'attributes' => ['lastUpdateTime']
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['name', 'courseID', 'uploaderID'], 'required'],
            [['name'], 'string'],
            [['courseID'], 'exist', 'skipOnError' => true, 'targetClass' => Course::class, 'targetAttribute' => ['courseID' => 'id']],
            [['uploaderID'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['uploaderID' => 'id']],
        ];
    }

    public function getCourse(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Course::class, ['id' => 'courseID']);
    }

    public function getGroups(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Group::class, ['courseID' => 'courseID']);
    }

    public function getUser(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'uploaderID']);
    }

    /**
     * @inheritdoc
     */
    public function getPath(): string
    {
        return Yii::getAlias(Yii::getAlias("@appdata/uploadedfiles/basefiles/") . $this->id);
    }

    public function fieldTypes(): array
    {
        return [
            'id' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'name' => new OAProperty(['type' => 'string']),
            'lastUpdateTime' => new OAProperty(['type' => 'string']),
            'courseID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'uploaderID' => new OAProperty(['ref' => '#/components/schemas/int_id']),
            'course' => new OAProperty(['ref' => '#/components/schemas/Common_CourseResource_Read']),
            'groups' => new OAProperty(['ref' => '#/components/schemas/Common_GroupResource_Read']),
            'user' => new OAProperty(['ref' => '#/components/schemas/Common_UserResource_Read']),
        ];
    }
}
