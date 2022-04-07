<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * @property-read bool $deletable Whether the currently authenticated
 *  user may delete the basefile.
 */
class PlagiarismBasefileResource extends \app\models\PlagiarismBasefile
{
    public function fields()
    {
        return [
            'id',
            'name',
            'lastUpdateTime',
        ];
    }

    public function extraFields()
    {
        return [
            'course',
            'deletable'
        ];
    }

    /**
     * Whether the currently authenticated user may delete the basefile.
     */
    public function getDeletable(): bool
    {
        $user = Yii::$app->user;
        return $user !== null &&
            ($user->id == $this->uploaderID || $user->can('manageCourse', ['courseID' => $this->courseID]));
    }

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'deletable' => new OAProperty(['type' => 'boolean']),
            ]
        );
    }
}
