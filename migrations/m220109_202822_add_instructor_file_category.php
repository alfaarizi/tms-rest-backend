<?php

use yii\db\Migration;

/**
 * Class m220109_202822_add_instructor_file_category
 */
class m220109_202822_add_instructor_file_category extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%instructor_files}}', 'category', "ENUM('Attachment','Test file') NOT NULL DEFAULT 'Attachment'");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%instructor_files}}', 'category');
    }
}
