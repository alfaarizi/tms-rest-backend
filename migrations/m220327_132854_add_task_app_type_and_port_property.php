<?php

use yii\db\Migration;

/**
 * Add fields for evaluation type and web application port for the Tasks table.
 */
class m220327_132854_add_task_app_type_and_port_property extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        /*
         * The  network port to expose from the Docker container.
         */
        $this->addColumn(
            '{{%tasks}}',
            'port',
            $this->integer()
        );

        /*
         * Application interface archetype
         */
        $this->addColumn(
            '{{%tasks}}',
            'appType',
            "ENUM('Console','Web')"
        );

        /*
        * Update existing images
        */
        $this->update('{{%tasks}}', ['appType' => 'Console'], ['not', ['imageName' => null]]);


        /*
         * Enforce default enum value
         */
        $this->alterColumn(
            '{{%tasks}}',
            'appType',
            "ENUM('Console','Web') DEFAULT 'Console'"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->update(
            '{{%tasks}}',
            [
                          'autoTest' => 0,
                          'showFullErrorMsg' => 0,
                          'imageName' => null,
                          'compileInstructions' => null,
                          'runInstructions' => null
                      ],
            [
                          'appType' => 'Web'
                      ]
        );
        $this->dropColumn('{{%tasks}}', 'port');
        $this->dropColumn('{{%tasks}}', 'appType');
    }
}
