<?php

use yii\db\Migration;

/**
 * Add new coumns for Groups table:
 * - day - The day of the class
 * - startTime - The start time of the class
 * - roomNumber - The number of the room where Group has class
 */
class m240928_115803_add_timetable_columns_to_groups extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->addColumn('{{%groups}}', 'day', $this->integer()->null()->comment('Day of the week (1=Monday, 7=Sunday)'));
        $this->addColumn('{{%groups}}', 'startTime', $this->time()->null()->comment('Starting time in HH:MM format'));
        $this->addColumn('{{%groups}}', 'roomNumber', $this->string(20)->null()->comment('Room number'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%groups}}', 'day');
        $this->dropColumn('{{%groups}}', 'startTime');
        $this->dropColumn('{{%groups}}', 'roomNumber');
    }
}
