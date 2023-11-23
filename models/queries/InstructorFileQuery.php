<?php

namespace app\models\queries;

use app\models\InstructorFile;
use yii\db\ActiveQuery;

class InstructorFileQuery extends ActiveQuery
{
    /**
     * {@inheritdoc}
     * @return InstructorFile[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return InstructorFile|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return InstructorFileQuery
     */
    public function onlyAttachments()
    {
        return $this->andWhere(['category' => InstructorFile::CATEGORY_ATTACHMENT]);
    }

    /**
     * @return InstructorFileQuery
     */
    public function onlyTestFiles()
    {
        return $this->andWhere(['category' => InstructorFile::CATEGORY_TESTFILE]);
    }

    /**
     * @return InstructorFileQuery
     */
    public function onlyWebAppTestSuites()
    {
        return $this->andWhere(['category' => InstructorFile::CATEGORY_WEB_TEST_SUITE]);
    }
}
