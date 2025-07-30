<?php

use yii\db\Migration;

/**
 * Add errorMessage field to StructuralRequirements table.
 * This field is used to store the error message for structural requirements.
 */
class m250721_154251_add_error_message_to_structural_requirements extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%structural_requirements}}', 'errorMessage', $this->text()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%structural_requirements}}', 'errorMessage');
    }
}
