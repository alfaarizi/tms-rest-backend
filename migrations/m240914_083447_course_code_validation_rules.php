<?php

use yii\db\Expression;
use yii\db\Migration;

/**
 * Add validation rules for course codes table:
 * - check if courseId is not null
 * - check if code is not null and the length is 30
 * - changes existing data to match the new rules
 */
class m240914_083447_course_code_validation_rules extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->delete('{{%course_codes}}', ['or', ['courseId' => null], ['code' => null]]);
        \Yii::$app->db->createCommand()
            ->update('{{%course_codes}}', ['code' => new Expression('SUBSTRING(code, 1, 30)')])
            ->execute();

        $this->alterColumn('{{%course_codes}}', 'courseId', $this->integer()->notNull());
        $this->alterColumn('{{%course_codes}}', 'code', $this->string(30)->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('{{%course_codes}}', 'courseId', $this->integer());
        $this->alterColumn('{{%course_codes}}', 'code', $this->string(255));
    }
}
