<?php

use yii\db\Migration;

/**
 * Add submission count restriction to tasks table
 */
class m240917_202253_add_submission_count_restriction extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%tasks}}',
            'submissionLimit',
            $this->integer()->notNull()->defaultValue(0)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%tasks}}', 'submissionLimit');
    }
}
