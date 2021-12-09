<?php

use yii\db\Migration;

/**
 * Set autoTest to false for Canvas tasks where the tester is not configured.
 * Previously the Canvas integration turned the tester on after synchronization.
 */
class m211207_083340_canvas_autotest_disable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->update(
            '{{%tasks}}',
            [
                'autoTest' => 0
            ],
            [
                'category' => 'Canvas tasks',
                'autoTest' => 1,
                'imageName' => null
            ]
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // empty on purpose
        return true;
    }
}
