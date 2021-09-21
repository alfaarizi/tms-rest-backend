<?php

use yii\db\Migration;

/**
 * Class m210529_125827_float_grades
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
