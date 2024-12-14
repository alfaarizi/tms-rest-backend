<?php

namespace app\models\queries;

use app\models\InstructorGroup;
use app\models\NotificationUser;
use app\models\Subscription;
use app\models\User;
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

    /**
     * Find notifications that are not dismissible or already dismissed by user
     */
    public function notDismissedBy(int $userID): NotificationQuery
    {
        return $this
            ->andWhere(['or',
                   ['not in', 'id', NotificationUser::find()->select('notificationID')->where(['userID' => $userID])],
                   ['dismissible' => false]
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

    /**
     * Find notifications that are not group level notifications
     */
    public function notGroupNotification(): NotificationQuery
    {
        return $this->andWhere(['groupID' => null]);
    }

    /**
     * Find group level notifications where the user is signed up as a student or is an instructor of the group.
     */
    public function groupNotification(int $userID): NotificationQuery
    {
        return $this
            ->andWhere(
                [
                    'or',
                    ['in', 'groupID', Subscription::find()->select('groupID')->where(['userID' => $userID])],
                    ['in', 'groupID', InstructorGroup::find()->select('groupID')->where(['userID' => $userID])],
                ]
            );
    }
}
