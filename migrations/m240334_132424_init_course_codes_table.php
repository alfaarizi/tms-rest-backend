<?php

class m240334_132424_init_course_codes_table extends \yii\db\Migration
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

        $this->createTable(
            '{{%course_codes}}',
            [
                'id' => $this->primaryKey(),
                'courseId' => $this->integer(),
                'code' => $this->string()
            ],
            $tableOptions
        );

        $this->createIndex('courseId', '{{%course_codes}}', ['courseId']);
        $this->addForeignKey(
            '{{%course_codes_ibfk_1}}',
            '{{%course_codes}}',
            ['courseId'],
            '{{%courses}}',
            ['id'],
            'CASCADE',
            'CASCADE'
        );

        $rows = $this->selectFindAll(['id', 'code'], '{{%courses}}', false);

        foreach ($rows as $row) {
            $this->insert('{{%course_codes}}', [
                'courseId' => $row['id'],
                'code' => $row['code'],
            ]);
        }

        $this->dropColumn('{{%courses}}', 'code');
    }


    public function selectFindAll(array $columns, string $table, bool $distinct)
    {
        return  (new \yii\db\Query())
            ->select($columns)
            ->distinct($distinct)
            ->from($table)
            ->all();
    }

    public function selectFindOne(array $columns, string $table, array $condition)
    {
        return  (new \yii\db\Query())
            ->select($columns)
            ->from($table)
            ->where($condition)
            ->one();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->addColumn('{{%courses}}', 'code', 'string');

        $courses = $this->selectFindAll(['id'], '{{%courses}}', false);

        foreach ($courses as $course) {
            $courseCode = $this->selectFindOne(['code'], '{{%course_codes}}', ['courseId' => $course['id']]);
            $this->update(
                '{{%courses}}',
                ['code' => $courseCode['code']],
                ['id' => $course['id']]
            );
        }

        $this->dropTable('{{%course_codes}}');
    }
}
