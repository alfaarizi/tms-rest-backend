<?php

namespace app\models\queries;

use app\models\ExamTest;
use app\models\Group;
use yii\db\ActiveQuery;
use yii\db\Expression;

class ExamTestQuery extends ActiveQuery
{
    /**
     * {@inheritdoc}
     * @return ExamTest[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return ExamTest|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    public function forGroups($groups)
    {
        return $this->andWhere(['in', 'groupID', $groups]);
    }

    public function forSemester($semesterID)
    {
        return $this->andWhere(
            [
                "in",
                "groupID", Group::find()->select('id')->where(['semesterID' => $semesterID])
            ]
        );
    }

    public function onlyActive()
    {
        return $this
            ->andWhere(["<=", "availablefrom", new Expression('NOW()')])
            ->andWhere([">=", "availableuntil", new Expression('NOW()')]);
    }

    public function onlyFuture()
    {
        return $this
            ->andWhere([">=", "availablefrom", new Expression('NOW()')]);
    }
}
