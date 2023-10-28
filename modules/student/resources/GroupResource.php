<?php

namespace app\modules\student\resources;

use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\models\Group;
use app\models\Subscription;
use app\resources\CourseResource;
use Yii;

/**
 * Resource class for module 'Group'
 */
class GroupResource extends Group
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'id',
            'number' => function() {
                return !$this->isExamGroup ? $this->number : null;
            },
            'course',
            'instructorNames',
            'timezone',
            'canvasUrl',
            'lastSyncTime',
            'isExamGroup'
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'notes'
        ];
    }

    public function fieldTypes(): array
    {
        $types = parent::fieldTypes();

        $types['number'] = new OAProperty(['type' => 'integer', 'nullable' => 'true']);
        $types['course'] = new OAProperty(['ref' => '#/components/schemas/Common_CourseResource_Read']);
        $types['instructorNames'] = new OAProperty(['type' => 'array', new OAItems(['type' => 'string'])]);
        $types['notes'] = new OAProperty(['type' => 'string']);

        return $types;
    }

    /**
     * @return array|string[]
     */
    public function getInstructorNames()
    {
        if ($this->isExamGroup) {
            return [];
        }

        return array_map(function ($user) {
            return $user->name;
        }, $this->instructors);
    }

    /**
     * @inheritdoc
     */
    public function getCourse()
    {
        return $this->hasOne(CourseResource::class, ['id' => 'courseID']);
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        $subscription = Subscription::findOne(
            [
                'groupID' => $this->id,
                'userID' => Yii::$app->user->id
            ]);

        return $subscription->notes;
    }
}
