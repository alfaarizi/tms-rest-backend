<?php

use yii\db\Migration;

/**
 * Adds a new field ('canvasErrors') to table 'groups'
 * This field stores the error messages which occurs during canvas synchronization
 * The errors are stored in a string, and they are separated by newline characters
 */
class m230613_123914_add_canvas_errors_to_groups extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%groups}}',
            'canvasErrors',
            $this->string(2000)->defaultValue(null)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%groups}}', 'canvasErrors');
    }
}
