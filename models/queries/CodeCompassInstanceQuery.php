<?php

namespace app\models\queries;

use app\models\CodeCompassInstance;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;

class CodeCompassInstanceQuery extends ActiveQuery
{
    /**
     * {@inheritdoc}
     * @return CodeCompassInstance[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return CodeCompassInstance|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    public function listWaitingOrderedByCreationTime(): CodeCompassInstanceQuery
    {
        return $this
            ->where(['status' => CodeCompassInstance::STATUS_WAITING])
            ->orderBy('creationTime');
    }

    public function findRunningForSubmissionId(string $submissionId): CodeCompassInstanceQuery
    {
        return $this
            ->where(['submissionId' => $submissionId])
            ->andWhere(['status' => CodeCompassInstance::STATUS_RUNNING]);
    }

    public function listExpired(): CodeCompassInstanceQuery
    {
        $timeoutMinutes = Yii::$app->params['codeCompass']['containerExpireMinutes'];
        $dateExpression = new Expression("NOW() - INTERVAL $timeoutMinutes MINUTE");
        return $this
            ->where(['<', 'creationTime', $dateExpression])
            ->andWhere(['status' => CodeCompassInstance::STATUS_RUNNING]);
    }
}
