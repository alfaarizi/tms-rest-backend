<?php

use yii\db\Migration;

/**
 * Add ON DELETE CASCADE to the codechecker_reports_results_fk foreign key.
 */
class m250331_141330_add_cacade_to_codechecker_results_foreign_key extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropForeignKey(
            '{{%codechecker_reports_results_fk}}',
            '{{%codechecker_reports}}'
        );

        $this->addForeignKey(
            '{{%codechecker_reports_results_fk}}',
            '{{%codechecker_reports}}',
            ['resultID'],
            '{{%codechecker_results}}',
            ['id'],
            'CASCADE',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey(
            '{{%codechecker_reports_results_fk}}',
            '{{%codechecker_reports}}'
        );

        $this->addForeignKey(
            '{{%codechecker_reports_results_fk}}',
            '{{%codechecker_reports}}',
            ['resultID'],
            '{{%codechecker_results}}',
            ['id']
        );
    }
}
