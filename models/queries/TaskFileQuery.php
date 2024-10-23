<?php

namespace app\models\queries;

use app\models\TaskFile;
use yii\db\ActiveQuery;

class TaskFileQuery extends ActiveQuery
{
    /**
     * {@inheritdoc}
     * @return TaskFile[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return TaskFile|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return TaskFileQuery
     */
    public function onlyAttachments()
    {
        return $this->andWhere(['category' => TaskFile::CATEGORY_ATTACHMENT]);
    }

    /**
     * @return TaskFileQuery
     */
    public function onlyTestFiles()
    {
        return $this->andWhere(['category' => TaskFile::CATEGORY_TESTFILE]);
    }

    /**
     * @return TaskFileQuery
     */
    public function onlyWebAppTestSuites()
    {
        return $this->andWhere(['category' => TaskFile::CATEGORY_WEB_TEST_SUITE]);
    }
}
