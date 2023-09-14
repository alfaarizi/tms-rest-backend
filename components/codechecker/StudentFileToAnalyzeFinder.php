<?php

namespace app\components\codechecker;

use app\models\StudentFile;
use yii\base\BaseObject;

/**
 * Contains logic to find the next student file to analyze
 */
class StudentFileToAnalyzeFinder extends BaseObject
{
    /**
     * Find the oldest studentFile that does not have a CodeChecker report with the least upload count.
     * @return StudentFile|null
     */
    public function findNext(): ?StudentFile
    {
        return StudentFile::find()
            ->alias('s')
            ->joinWith('task t', false, 'INNER JOIN')
            ->andWhere(['t.staticCodeAnalysis' => 1])
            ->andWhere(['not', ['t.imageName' => null]])
            ->andWhere(['s.codeCheckerResultID' => null])
            ->andWhere(
                [
                    'not in',
                    'isAccepted',
                    [
                        StudentFile::IS_ACCEPTED_REJECTED,
                        StudentFile::IS_ACCEPTED_ACCEPTED,
                        StudentFile::IS_ACCEPTED_LATE_SUBMISSION,
                        StudentFile::IS_ACCEPTED_CORRUPTED,
                    ]
                ]
            )
            ->andWhere(['not', ['uploadCount' => 0]])
            ->orderBy(['s.uploadCount' => SORT_ASC, 's.uploadTime' => SORT_ASC])
            ->one();
    }
}
