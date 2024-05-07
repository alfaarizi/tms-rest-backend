<?php

use yii\db\Migration;

/**
 * Class m240331_191401_set_default_show_full_err_msg_to_true
 * This migration sets the default value 1 (true) to showFullErrorMsg,
 * so by default the students see what error message they get
 * even if the teacher does not explicitly set it.
 */
class m240331_191401_set_default_show_full_err_msg_to_true extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->alterColumn(
            '{{%tasks}}',
            'showFullErrorMsg',
            $this->boolean()->notNull()->defaultValue('1'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

        $this->alterColumn(
            '{{%tasks}}',
            'showFullErrorMsg',
            $this->boolean()->notNull()->defaultValue('0'),
        );
    }
}
