<?php

use yii\db\Migration;

/**
 * Enable float values as grades.
 */
class m210830_115627_float_grades extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%student_files}}', 'grade', $this->float());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('{{%student_files}}', 'grade', $this->integer());
    }
}
