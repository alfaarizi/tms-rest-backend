<?php

namespace app\models\queries;

use app\models\WebAppExecution;
use app\models\Submission;
use yii\db\ActiveQuery;
use yii\db\Expression;

class WebAppExecutionQuery extends ActiveQuery
{
    /**
     * {@inheritDoc}
     * @return WebAppExecution[]
     */
    public function all($db = null): array
    {
        return parent::all($db);
    }

    /**
     * {@inheritDoc}
     * @return WebAppExecution|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * Fetches running remote executions of a student file instantiated by the given instructor
     *
     * @param Submission $submission the task to which executions belong
     * @param integer $instructorID the id of the instructor started the execution
     * @return WebAppExecutionQuery
     */
    public function executionsOf(Submission $submission, $instructorID): WebAppExecutionQuery
    {
        return $this->andWhere(
            [
                'submissionID' => $submission->id,
                'instructorID' => $instructorID
            ]
        );
    }

    public function expired(): WebAppExecutionQuery
    {
        return $this->andWhere(
            [
                "<=",
                "shutdownAt",
                new Expression('NOW()')
            ]
        );
    }
}
