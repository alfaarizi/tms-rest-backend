<?php

namespace app\models\queries;

use app\models\NotificationUser;
use yii\db\ActiveQuery;
use yii\db\Expression;

class NotificationQuery extends ActiveQuery
{
    /**
     * {@inheritdoc}
     * @return \app\models\Notification[]|array
     */
    public function all($db = null): array
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return array|\yii\db\ActiveRecord|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    public function notDismissedBy(int $userID): NotificationQuery
    {
        return $this
            ->andWhere(['or',
                   ['not in', 'id', NotificationUser::find()->select('notificationID')->where(['userID' => $userID])],
                   ['dismissable' => false]
            ]);
    }

    /**
     * Find active notifications
     */
    public function findAvailable(): NotificationQuery
    {
        return $this->andWhere(
            [
                'and',
                ['<=', 'startTime', new Expression('NOW()')],
                ['>', 'endTime', new Expression('NOW()')]
            ]
        );
    }
}
