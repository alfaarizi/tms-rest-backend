<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use app\models\Notification;
use yii\helpers\ArrayHelper;

/**
 * Resource class for module 'Notification'
 */
class NotificationResource extends Notification
{
    public const SCENARIO_UPDATE = 'update';

    /**
     * @inheritdoc
     */
    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_UPDATE] = ['id', 'message', 'startTime', 'endTime', 'dismissible'];
        $scenarios[self::SCENARIO_DEFAULT] = ['id', 'groupID', 'message', 'startTime', 'endTime', 'dismissible'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        return [
            'id',
            'groupID',
            'message',
            'startTime',
            'endTime',
            'dismissible',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return ArrayHelper::merge(
            parent::rules(),
            [
                [['groupID'], 'required'],
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        return [];
    }
}
