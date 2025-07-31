<?php

namespace app\components\codechecker;

use app\models\Submission;
use yii\base\BaseObject;

/**
 * Contains logic to find the next student file to analyze
 */
class SubmissionToAnalyzeFinder extends BaseObject
{
    /**
     * Find the oldest submission that does not have a CodeChecker report with the least upload count.
     * @return Submission|null
     */
    public function findNext(): ?Submission
    {
        return Submission::find()
            ->alias('s')
            ->joinWith('task t', false, 'INNER JOIN')
            ->andWhere(['t.staticCodeAnalysis' => 1])
            ->andWhere(['not', ['t.imageName' => null]])
            ->andWhere(['s.codeCheckerResultID' => null])
            ->andWhere(
                [
                    'not in',
                    'status',
                    [
                        Submission::STATUS_REJECTED,
                        Submission::STATUS_ACCEPTED,
                        Submission::STATUS_CORRUPTED,
                    ]
                ]
            )
            ->andWhere(['not', ['uploadCount' => 0]])
            ->orderBy(['s.uploadCount' => SORT_ASC, 's.uploadTime' => SORT_ASC])
            ->one();
    }
}
