<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Introduce JPlag plagiarism checks, refactor out Moss-specific details
 * from the `plagiarisms` table.
 */
class m230325_000000_introduce_jplag extends Migration
{
    public function up()
    {
        $this->createTable('{{%plagiarisms_moss}}', [
            'id' => $this->primaryKey(),
            'plagiarismId' => $this->integer()->notNull()->unique(),
            'response' => $this->string(300),
            'ignoreThreshold' => $this->smallInteger()->notNull()->defaultValue(10),
        ]);

        $this->addForeignKey(
            '{{%plagiarisms_moss_ibfk_1}}',
            '{{%plagiarisms_moss}}',
            'plagiarismId',
            '{{%plagiarisms}}',
            'id',
            'CASCADE'
        );

        $this->createTable('{{%plagiarisms_jplag}}', [
            'id' => $this->primaryKey(),
            'plagiarismId' => $this->integer()->notNull()->unique(),
            'ignoreFiles' => $this->text()->notNull()->defaultExpression("('')"),
            'tune' => $this->integer()->notNull()->defaultValue(60),
        ]);

        $this->addForeignKey(
            '{{%plagiarisms_jplag_ibfk_1}}',
            '{{%plagiarisms_jplag}}',
            'plagiarismId',
            '{{%plagiarisms}}',
            'id',
            'CASCADE'
        );

        foreach ((new Query())->from('{{%plagiarisms}}')->each() as $plagiarism) {
            $this->insert('{{%plagiarisms_moss}}', [
                'plagiarismId' => $plagiarism['id'],
                'response' => $plagiarism['response'],
                'ignoreThreshold' => $plagiarism['ignoreThreshold'],
            ]);
        }

        $this->dropColumn(
            '{{%plagiarisms}}',
            'response'
        );
        $this->dropColumn(
            '{{%plagiarisms}}',
            'ignoreThreshold'
        );
        $this->addColumn(
            '{{%plagiarisms}}',
            'generateTime',
            $this->dateTime()
        );
        $this->addColumn(
            '{{%plagiarisms}}',
            'type',
            "ENUM('moss','jplag') NOT NULL DEFAULT 'moss'"
        );
        $this->alterColumn(
            '{{%plagiarisms}}',
            'type',
            "ENUM('moss','jplag') NOT NULL"
        );
    }

    public function down()
    {
        $this->dropColumn(
            '{{%plagiarisms}}',
            'type'
        );
        $this->dropColumn(
            '{{%plagiarisms}}',
            'generateTime'
        );
        $this->addColumn(
            '{{%plagiarisms}}',
            'ignoreThreshold',
            $this->smallInteger()->notNull()->defaultValue('10')
        );
        $this->addColumn(
            '{{%plagiarisms}}',
            'response',
            $this->string(300)
        );

        foreach ((new Query())->from('{{%plagiarisms_moss}}')->each() as $mossPlagiarism) {
            $this->update('{{%plagiarisms}}', [
                'response' => $mossPlagiarism['response'],
                'ignoreThreshold' => $mossPlagiarism['ignoreThreshold'],
            ], ['id' => $mossPlagiarism['plagiarismId']]);
        }
        $this->dropForeignKey('{{%plagiarisms_jplag_ibfk_1}}', '{{%plagiarisms_jplag}}');
        $this->dropTable('{{%plagiarisms_jplag}}');
        $this->dropForeignKey('{{%plagiarisms_moss_ibfk_1}}', '{{%plagiarisms_moss}}');
        $this->dropTable('{{%plagiarisms_moss}}');
    }
}
