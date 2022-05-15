<?php

use yii\db\Migration;

/**
 * Adds CodeCompass integration tables and fields.
 */
class m220318_132947_codecompass_integration extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        }

        $this->addColumn('{{%tasks}}', 'codeCompassCompileInstructions', $this->string(1000));
        $this->addColumn('{{%tasks}}', 'codeCompassPackagesInstallInstructions', $this->string(500));

        $this->createTable(
            '{{%codecompass_instances}}',
            [
                'id' => $this->primaryKey(),
                'studentFileId' => $this->integer()->notNull(),
                'containerId' => $this->string(50),
                'status' => $this->string(10)->notNull(),
                'instanceStarterUserId' => $this->integer()->notNull(),
                'port' => $this->integer(5),
                'errorLogs' => $this->text(),
                'creationTime' => $this->dateTime(),
                'username' => $this->string(20),
                'password' => $this->string(20)
            ],
            $tableOptions
        );

        $this->addForeignKey(
            '{{%codecompass_instances_ibfk_1}}',
            '{{%codecompass_instances}}',
            ['studentFileId'],
            '{{%student_files}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );

        $this->addForeignKey(
            '{{%codecompass_instances_ibfk_2}}',
            '{{%codecompass_instances}}',
            ['instanceStarterUserId'],
            '{{%users}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%tasks}}', 'codeCompassCompileInstructions');
        $this->dropColumn('{{%tasks}}', 'codeCompassPackagesInstallInstructions');

        $this->dropTable('{{%codecompass_instances}}');
    }
}
