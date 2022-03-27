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
        echo "m220327_132854_add_task_app_type_and_port_property cannot be reverted, defined port and appType can't be inferred\n";

        return false;
    }
}
