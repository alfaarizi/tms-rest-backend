<?php

use yii\db\Migration;

/**
 * Extend automated evaluation test cases with input command line arguments.
 */
class m220102_174316_testcase_args extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%test_cases}}', 'arguments', $this->text()->notNull()->after('taskID'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%test_cases}}', 'arguments');
    }
}
