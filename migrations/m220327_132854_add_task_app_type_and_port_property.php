<?php

use yii\db\Migration;

/**
 * Class m220327_132854_add_task_app_type_and_port_property
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

        $this->update('{{%tasks}}', ['appType' => 'Console'], ['not', ['imageName' => null]]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->update('{{%tasks}}',
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
