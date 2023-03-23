<?php

use yii\db\Migration;

/**
 * Adds tables to store CodeChecker run results and reports.
 * It also extends the tasks table to store configuration related to static code analysis.
 */
class m230216_181544_codechecker_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(
            '{{%codechecker_results}}',
            [
                'id' => $this->primaryKey(),
                'token' => $this->string(32)->notNull(),
                'studentFileID' => $this->integer()->notNull(),
                'createdAt' => $this->dateTime()->notNull(),
                'status' => "ENUM('In Progress', 'No Issues', 'Issues Found', 'Analysis Failed' , 'Runner Error') NOT NULL",
                'stdout' => $this->text(),
                'stderr' => $this->text(),
                'runnerErrorMessage' => $this->text(),
            ]
        );

        $this->createTable(
            '{{%codechecker_reports}}',
            [
                'id' => $this->primaryKey(),
                'resultID' => $this->integer()->notNull(),
                'filePath' => $this->string(),
                'reportHash' => $this->string()->notNull(),
                'line' => $this->integer()->notNull(),
                'column' => $this->integer()->notNull(),
                'checkerName' => $this->string()->notNull(),
                'analyzerName' => $this->string()->notNull(),
                'severity' => "ENUM('Unspecified', 'Style', 'Low', 'Medium', 'High', 'Critical') NOT NULL",
                'category' => $this->string()->notNull(),
                'message' => $this->string(1000),
                'plistFileName' => $this->string()->notNull()
            ]
        );

        $this->addColumn('{{%student_files}}', 'codeCheckerResultID', $this->integer()->null());

        $this->addForeignKey(
            '{{%codechecker_results_student_files_fk}}',
            '{{%codechecker_results}}',
            ['studentFileId'],
            '{{%student_files}}',
            ['id'],
        );
        $this->addForeignKey(
            '{{%codechecker_reports_results_fk}}',
            '{{%codechecker_reports}}',
            ['resultID'],
            '{{%codechecker_results}}',
            ['id'],
        );

        $this->addColumn('{{%tasks}}', 'staticCodeAnalysis', $this->boolean()->notNull()->defaultValue(false));
        $this->addColumn('{{%tasks}}', 'staticCodeAnalyzerTool', $this->string());
        $this->addColumn('{{%tasks}}', 'staticCodeAnalyzerInstructions', $this->string(1000));
        $this->addColumn('{{%tasks}}', 'codeCheckerCompileInstructions', $this->string(1000));
        $this->addColumn('{{%tasks}}', 'codeCheckerToggles', $this->string(1000));
        $this->addColumn('{{%tasks}}', 'codeCheckerSkipFile', $this->string(1000));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('{{%codechecker_results_student_files_fk}}', '{{%codechecker_results}}');
        $this->dropForeignKey('{{%codechecker_reports_results_fk}}', '{{%codechecker_reports}}');
        $this->dropColumn('{{%student_files}}', 'codeCheckerResultID');

        $this->dropTable('{{%codechecker_results}}');
        $this->dropTable('{{%codechecker_reports}}');

        $this->dropColumn('{{%tasks}}', 'staticCodeAnalysis');
        $this->dropColumn('{{%tasks}}', 'staticCodeAnalyzerTool');
        $this->dropColumn('{{%tasks}}', 'staticCodeAnalyzerInstructions');
        $this->dropColumn('{{%tasks}}', 'codeCheckerCompileInstructions');
        $this->dropColumn('{{%tasks}}', 'codeCheckerToggles');
        $this->dropColumn('{{%tasks}}', 'codeCheckerSkipFile');
    }
}
