<?php

use yii\db\Migration;

/**
 * Add table and columns to support plagiarism basefiles and persisting
 * Moss plagiarism results.
 */
class m220123_151721_basefiles_and_moss_download extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->createTable('{{%plagiarism_basefiles}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'lastUpdateTime' => $this->dateTime(),
            'courseID' => $this->integer()->notNull(),
            'uploaderID' => $this->integer()->notNull()
        ]);
        $this->addForeignKey(
            '{{%plagiarism_basefiles_ibfk_1}}',
            '{{%plagiarism_basefiles}}',
            'courseID',
            '{{%courses}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            '{{%plagiarism_basefiles_ibfk_2}}',
            '{{%plagiarism_basefiles}}',
            'uploaderID',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addColumn(
            '{{%plagiarisms}}',
            'token',
            $this->char(32)->comment(
                'A 32-character hexadecimal string (e.g. an MD5 hash)' .
                ' used to prevent unauthorized users from viewing' .
                ' the result of random plagiarism checks by guessing' .
                ' their primary IDs.'
            )
        );
        $this->addColumn(
            '{{%plagiarisms}}',
            'baseFileIDs',
            $this->text()->notNull()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->dropColumn(
            '{{%plagiarisms}}',
            'baseFileIDs'
        );
        $this->dropColumn(
            '{{%plagiarisms}}',
            'token'
        );
        $this->dropForeignKey('{{%plagiarism_basefiles_ibfk_1}}', '{{%plagiarism_basefiles}}');
        $this->dropForeignKey('{{%plagiarism_basefiles_ibfk_2}}', '{{%plagiarism_basefiles}}');
        $this->dropTable('{{%plagiarism_basefiles}}');
    }
}
