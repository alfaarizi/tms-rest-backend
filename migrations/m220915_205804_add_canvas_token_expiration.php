<?php

use yii\db\Migration;

/**
 * Stores the canvas token expiration date in the 'users' table
 */
class m220915_205804_add_canvas_token_expiration extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%users}}', 'canvasTokenExpiry', $this->dateTime()->defaultValue(null)->after('refreshToken'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%users}}', 'canvasTokenExpiry');
    }
}
