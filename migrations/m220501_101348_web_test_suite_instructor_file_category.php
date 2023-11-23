<?php

use yii\db\Migration;

/**
 * Adds 'Web test suite' to the possible options of instructor_files.category
 */
class m220501_101348_web_test_suite_instructor_file_category extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            '{{%instructor_files}}',
            'category',
            "ENUM('Attachment', 'Test file', 'Web test suite') DEFAULT 'Attachment'"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->addColumn(
            '{{%instructor_files}}',
            'category',
            "ENUM('Attachment', 'Test file') NOT NULL DEFAULT 'Attachment'"
        );
    }
}
