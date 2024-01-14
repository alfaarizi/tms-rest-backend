<?php

use yii\db\Migration;

/**
 * Increase Course::code field length from 20 chars to 30.
 */
class m240114_211302_increase_course_code_length extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            '{{%courses}}',
            'code',
            $this->string(30)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn(
            '{{%courses}}',
            'code',
            $this->string(20)
        );
    }
}
