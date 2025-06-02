<?php

use yii\db\Migration;
use app\tests\DateFormat;
use yii\db\Query;

/**
 * Adds personal late deadline to submission table and removes existing late submissions
 */
class m250322_132314_add_personal_late_deadline extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%submissions}}', 'personalDeadline', $this->dateTime()->defaultValue(null));

        $semesterEnd = (new DateTime('+3 month'))->format(DateFormat::MYSQL);

        $submissionsFromPastSemesters = (new Query())
            ->select(['{{%submissions}}.id', '{{%semesters}}.actual', '{{%submissions}}.uploadCount'])
            ->from('{{%submissions}}')
            ->innerJoin('{{%tasks}}', '{{%tasks}}.id = {{%submissions}}.taskID')
            ->innerJoin('{{%semesters}}', '{{%tasks}}.semesterID = {{%semesters}}.id')
            ->where("{{%submissions}}.`status` = 'Late Submission'");

        //Remove late submission status from submissions
        foreach ($submissionsFromPastSemesters->each() as $submission) {
            $isActualSemester = (bool)$submission['actual'];
            $uploadCount = (int)$submission['uploadCount'];
            $this->update('{{%submissions}}', [
                'status' => $uploadCount > 0 ? 'Uploaded' : 'No Submission',
                'personalDeadline' => !$isActualSemester ? null : $semesterEnd
            ], ['id' => $submission['id']]);
        }

        $this->alterColumn(
            '{{%submissions}}',
            'status',
            "ENUM('No Submission', 'Uploaded', 'Accepted', 'Rejected', 'Passed', 'Failed', 'Corrupted')"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%submissions}}', 'personalDeadline');

        $this->alterColumn(
            '{{%submissions}}',
            'status',
            "ENUM('No Submission', 'Uploaded', 'Accepted', 'Rejected', 'Late Submission', 'Passed', 'Failed', 'Corrupted')"
        );
    }
}
