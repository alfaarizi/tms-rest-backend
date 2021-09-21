<?php

use yii\db\Migration;

/**
 * Create assignment tables.
 */
class m210830_084633_init_assignment_tables extends Migration
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

        // semesters
        $this->createTable(
            '{{%semesters}}',
            [
                'id' => $this->primaryKey(),
                'name' => $this->string(10)->notNull(),
                'actual' => $this->boolean()->notNull(),
            ],
            $tableOptions
        );
        $this->createIndex('name', '{{%semesters}}', ['name'], true);

        // users
        $this->createTable(
            '{{%users}}',
            [
                'id' => $this->primaryKey(),
                'name' => $this->string(50),
                'neptun' => $this->char(6)->notNull(),
                'email' => $this->string(50),
                'locale' => $this->string(5)->notNull()->defaultValue('en-US'),
                'lastLoginTime' => $this->dateTime(),
                'lastLoginIP' => $this->string(40),
                'canvasID' => $this->integer(),
                'canvasToken' => $this->string(200),
                'refreshToken' => $this->string(200),
            ],
            $tableOptions
        );
        $this->createIndex('canvasID', '{{%users}}', ['canvasID']);
        $this->createIndex('neptun', '{{%users}}', ['neptun'], true);

        // courses
        $this->createTable(
            '{{%courses}}',
            [
                'id' => $this->primaryKey(),
                'name' => $this->string(100)->notNull(),
                'code' => $this->string(20),
            ],
            $tableOptions
        );

        // groups
        $this->createTable(
            '{{%groups}}',
            [
                'id' => $this->primaryKey(),
                'number' => $this->integer(),
                'courseID' => $this->integer(),
                'semesterID' => $this->integer(),
                'isExamGroup' => $this->boolean()->notNull()->defaultValue('0'),
                'canvasCourseID' => $this->integer(),
                'canvasSectionID' => $this->integer(),
                'synchronizerID' => $this->integer(),
            ],
            $tableOptions
        );
        $this->createIndex('courseCode', '{{%groups}}', ['number', 'courseID', 'semesterID'], true);
        $this->createIndex('courseID', '{{%groups}}', ['courseID']);
        $this->createIndex('semesterID', '{{%groups}}', ['semesterID']);
        $this->addForeignKey(
            '{{%groups_ibfk_1}}',
            '{{%groups}}',
            ['courseID'],
            '{{%courses}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            '{{%groups_ibfk_2}}',
            '{{%groups}}',
            ['semesterID'],
            '{{%semesters}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            '{{%groups_ibfk_3}}',
            '{{%groups}}',
            ['synchronizerID'],
            '{{%users}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );

        // instructor_courses
        $this->createTable(
            '{{%instructor_courses}}',
            [
                'id' => $this->primaryKey(),
                'userID' => $this->integer()->notNull(),
                'courseID' => $this->integer()->notNull(),
            ],
            $tableOptions
        );
        $this->createIndex('courseID', '{{%instructor_courses}}', ['courseID', 'userID'], true);
        $this->createIndex('userID', '{{%instructor_courses}}', ['userID']);
        $this->addForeignKey(
            '{{%instructor_courses_ibfk_1}}',
            '{{%instructor_courses}}',
            ['courseID'],
            '{{%courses}}',
            ['id'],
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            '{{%instructor_courses_ibfk_2}}',
            '{{%instructor_courses}}',
            ['userID'],
            '{{%users}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );

        // instructor_groups
        $this->createTable(
            '{{%instructor_groups}}',
            [
                'id' => $this->primaryKey(),
                'userID' => $this->integer()->notNull(),
                'groupID' => $this->integer()->notNull(),
            ],
            $tableOptions
        );
        $this->createIndex('groupID', '{{%instructor_groups}}', ['groupID', 'userID'], true);
        $this->createIndex('userID', '{{%instructor_groups}}', ['userID']);
        $this->addForeignKey(
            '{{%instructor_groups_ibfk_1}}',
            '{{%instructor_groups}}',
            ['groupID'],
            '{{%groups}}',
            ['id'],
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            '{{%instructor_groups_ibfk_2}}',
            '{{%instructor_groups}}',
            ['userID'],
            '{{%users}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );

        // subscriptions
        $this->createTable(
            '{{%subscriptions}}',
            [
                'id' => $this->primaryKey(),
                'userID' => $this->integer()->notNull(),
                'isAccepted' => $this->boolean()->notNull()->defaultValue('1'),
                'groupID' => $this->integer()->notNull(),
                'semesterID' => $this->integer(),
            ],
            $tableOptions
        );
        $this->createIndex('courseID', '{{%subscriptions}}', ['groupID']);
        $this->createIndex('semesterID', '{{%subscriptions}}', ['semesterID', 'groupID', 'userID'], true);
        $this->createIndex('userID', '{{%subscriptions}}', ['userID']);
        $this->addForeignKey(
            '{{%subscriptions_ibfk_1}}',
            '{{%subscriptions}}',
            ['groupID'],
            '{{%groups}}',
            ['id'],
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            '{{%subscriptions_ibfk_2}}',
            '{{%subscriptions}}',
            ['semesterID'],
            '{{%semesters}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            '{{%subscriptions_ibfk_3}}',
            '{{%subscriptions}}',
            ['userID'],
            '{{%users}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );

        // tasks
        $this->createTable(
            '{{%tasks}}',
            [
                'id' => $this->primaryKey(),
                'name' => $this->string(25)->notNull(),
                'groupID' => $this->integer()->notNull(),
                'description' => $this->text()->notNull(),
                'category' => $this->string(),
                'softDeadline' => $this->dateTime(),
                'hardDeadline' => $this->dateTime()->notNull(),
                'available' => $this->dateTime(),
                'semesterID' => $this->integer()->notNull(),
                'isVersionControlled' => $this->smallInteger(),
                'createrID' => $this->integer(),
                'autoTest' => $this->boolean()->notNull()->defaultValue('0'),
                'testOS' => $this->string()->notNull()->defaultValue('linux'),
                'showFullErrorMsg' => $this->boolean()->notNull()->defaultValue('0'),
                'imageName' => $this->string(),
                'compileInstructions' => $this->string(1000),
                'runInstructions' => $this->string(),
                'canvasID' => $this->integer(),
            ],
            $tableOptions
        );
        $this->createIndex('canvasID', '{{%tasks}}', ['canvasID']);
        $this->createIndex('category', '{{%tasks}}', ['category']);
        $this->createIndex('instructorID', '{{%tasks}}', ['groupID']);
        $this->createIndex('semesterID', '{{%tasks}}', ['semesterID']);
        $this->addForeignKey(
            '{{%tasks_ibfk_1}}',
            '{{%tasks}}',
            ['semesterID'],
            '{{%semesters}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            '{{%tasks_ibfk_2}}',
            '{{%tasks}}',
            ['groupID'],
            '{{%groups}}',
            ['id'],
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            '{{%tasks_ibfk_3}}',
            '{{%tasks}}',
            ['createrID'],
            '{{%users}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );

        // instructor_files
        $this->createTable(
            '{{%instructor_files}}',
            [
                'id' => $this->primaryKey(),
                'name' => $this->string(200)->notNull(),
                'uploadTime' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
                'taskID' => $this->integer()->notNull(),
            ],
            $tableOptions
        );
        $this->createIndex('taskID', '{{%instructor_files}}', ['taskID']);
        $this->addForeignKey(
            '{{%instructorFiles_ibfk_1}}',
            '{{%instructor_files}}',
            ['taskID'],
            '{{%tasks}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );

        // student_files
        $this->createTable(
            '{{%student_files}}',
            [
                'id' => $this->primaryKey(),
                'name' => $this->string(200)->notNull(),
                'uploadTime' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
                'taskID' => $this->integer()->notNull(),
                'uploaderID' => $this->integer()->notNull(),
                'isAccepted' => $this->string()->notNull(),
                'grade' => $this->smallInteger(),
                'notes' => $this->text()->notNull(),
                'isVersionControlled' => $this->smallInteger(),
                'graderID' => $this->integer(),
                'errorMsg' => $this->text(),
                'canvasID' => $this->integer(),
            ],
            $tableOptions
        );
        $this->createIndex('taskID', '{{%student_files}}', ['taskID']);
        $this->createIndex('uploaderID', '{{%student_files}}', ['uploaderID']);
        $this->addForeignKey(
            '{{%studentFiles_ibfk_1}}',
            '{{%student_files}}',
            ['taskID'],
            '{{%tasks}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            '{{%studentFiles_ibfk_2}}',
            '{{%student_files}}',
            ['uploaderID'],
            '{{%users}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            '{{%studentFiles_ibfk_3}}',
            '{{%student_files}}',
            ['graderID'],
            '{{%users}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );

        // test_cases
        $this->createTable(
            '{{%test_cases}}',
            [
                'id' => $this->primaryKey(),
                'taskID' => $this->integer()->notNull(),
                'input' => $this->text()->notNull(),
                'output' => $this->text()->notNull(),
            ],
            $tableOptions
        );
        $this->createIndex('taskID', '{{%test_cases}}', ['taskID']);
        $this->addForeignKey(
            '{{%test_cases_ibfk_1}}',
            '{{%test_cases}}',
            ['taskID'],
            '{{%tasks}}',
            ['id'],
            'CASCADE',
            'CASCADE'
        );

        // plagiarisms
        $this->createTable(
            '{{%plagiarisms}}',
            [
                'id' => $this->primaryKey(),
                'requesterID' => $this->integer()->notNull(),
                'taskIDs' => $this->text()->notNull(),
                'userIDs' => $this->text()->notNull(),
                'semesterID' => $this->integer()->notNull(),
                'name' => $this->string(30)->notNull(),
                'description' => $this->text()->notNull(),
                'response' => $this->string(300),
                'ignoreThreshold' => $this->smallInteger()->notNull()->defaultValue('10'),
            ],
            $tableOptions
        );
        $this->createIndex('requesterID', '{{%plagiarisms}}', ['requesterID']);
        $this->createIndex('semesterID', '{{%plagiarisms}}', ['semesterID']);
        $this->addForeignKey(
            '{{%plagiarisms_ibfk_1}}',
            '{{%plagiarisms}}',
            ['requesterID'],
            '{{%users}}',
            ['id'],
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            '{{%plagiarisms_ibfk_2}}',
            '{{%plagiarisms}}',
            ['semesterID'],
            '{{%semesters}}',
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
        $this->dropTable('{{%plagiarisms}}');
        $this->dropTable('{{%test_cases}}');
        $this->dropTable('{{%student_files}}');
        $this->dropTable('{{%instructor_files}}');
        $this->dropTable('{{%tasks}}');
        $this->dropTable('{{%subscriptions}}');
        $this->dropTable('{{%instructor_groups}}');
        $this->dropTable('{{%instructor_courses}}');
        $this->dropTable('{{%groups}}');
        $this->dropTable('{{%courses}}');
        $this->dropTable('{{%users}}');
        $this->dropTable('{{%semesters}}');
    }
}
