<?php

namespace app\models\queries;

use app\models\QuizTestInstance;

class QuizTestInstanceQuery extends \yii\db\ActiveQuery
{
    /**
     * {@inheritdoc}
     * @return QuizTestInstance[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return QuizTestInstance|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    public function forTests($tests)
    {
        return $this->andWhere(['in', 'testID', $tests]);
    }

    public function forUser($userID)
    {
        return $this->andWhere(['userID' => $userID]);
    }

    public function onlySubmitted(bool $submitted)
    {
        return $this->andWhere(['submitted' => $submitted]);
    }
}
