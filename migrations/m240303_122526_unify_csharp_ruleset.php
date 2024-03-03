<?php

use yii\db\Migration;

/**
 * Class m240303_122526_unify_csharp_ruleset
 */
class m240303_122526_unify_csharp_ruleset extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("UPDATE {{%tasks}} SET staticCodeAnalyzerInstructions = REPLACE(staticCodeAnalyzerInstructions, 'https://gitlab.com/tms-elte/backend-core/-/snippets/2518152/raw/main/eva2023.txt', 'https://gitlab.com/tms-elte/backend-core/-/snippets/2518152/raw/main/diagnostics.txt')");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // This migration removes the usage of an outdated SA ruleset, which can't be and shouldn't be reverted.
        return true;
    }
}
