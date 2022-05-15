<?php

use yii\db\Migration;

/**
 * Add table for web application executions.
 */
class m220327_201850_create_remote_execution extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        /*
         * Table of running web app instances
         */
        $this->createTable('{{%web_app_executions}}', [
            'id' => $this->primaryKey(),
            //Solution running in the container
            'studentFileID' => $this->integer()->notNull(),
            //Instructor who launched the container
            'instructorID' => $this->integer()->notNull(),
            //url of the docker host
            'dockerHostUrl' => $this->string()->notNull(),
            //Host port bound to the container
            'port' => $this->integer()->notNull(),
            //Container name
            'containerName' => $this->string(),
            //Container start up time
            'startedAt' => $this->dateTime(),
            //When to shutdown the container
            'shutdownAt' => $this->dateTime(),
        ]);

        //Can't serve multiple instances on the same host and port binding
        $this->createIndex(
            '{{%webAppExecutions_ind_1}}',
            '{{%web_app_executions}}',
            ['dockerHostUrl', 'port'],
            true
        );

        $this->addForeignKey(
            '{{%webAppExecutions_ibfk_1}}',
            '{{%web_app_executions}}',
            'instructorID',
            '{{%users}}',
            'id'
        );

        $this->addForeignKey(
            '{{%webAppExecutions_ibfk_2}}',
            '{{%web_app_executions}}',
            'studentFileID',
            '{{%student_files}}',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('{{%webAppExecutions_ibfk_1}}', '{{%web_app_executions}}');
        $this->dropForeignKey('{{%webAppExecutions_ibfk_2}}', '{{%web_app_executions}}');
        $this->dropIndex('{{%webAppExecutions_ind_1}}', '{{%web_app_executions}}');
        $this->dropTable('{{%web_app_executions}}');
    }
}
