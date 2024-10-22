<?php

namespace app\commands;

use app\models\StudentFile;
use Yii;
use app\models\Semester;
use app\models\Group;
use yii\db\Query;
use yii\console\ExitCode;
use yii\console\widgets\Table;
use yii\helpers\Console;

/**
 * Manages reports and statistics.
 */
class ReportController extends BaseController
{
    /**
     * @var string The email address to send a copy of the report.
     */
    public $email;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return ['email'];
    }

    /**
     * @inheritdoc
     */
    public function optionAliases()
    {
        return [];
    }

    /**
     * Reports all students who did not submit an accepted solution for the
     * nth task of a course matching a name pattern in the defined semester.
     *
     * @param $course string The course name pattern.
     * @param $taskSerial int The nth task in each group of the course to consider.
     * @param $group int|string The group number boundaries to consider (default: 1-89).
     * @param $semester string|null The queried semester (default: actual).
     * @return int Error code.
     */
    public function actionNonAccepted($course, $taskSerial, $group = '1-89', $semester = null)
    {
        // Argument validation
        $taskSerial = (int)$taskSerial;
        if ($taskSerial < 1) {
            $this->stderr('Task serial must be a positive integer.');
            return ExitCode::USAGE;
        }
        $group = preg_split("/[\\-,;]/", $group);
        if (count($group) == 0 || count($group) > 2) {
            $this->stderr('Invalid group number boundaries.');
            return ExitCode::USAGE;
        }
        $group[0] = intval($group[0]);
        $group[1] = isset($group[1]) ? intval($group[1]) : $group[0];

        // Fetch the requested semester object
        if (empty($semester)) {
            $semesterObj = Semester::findOne(['actual' => true]);
        } else {
            $semesterObj = Semester::findOne(['name' => $semester]);
            if ($semesterObj == null) {
                $this->stderr("Failed to find semester '$semester'." . PHP_EOL, Console::FG_RED);
                return ExitCode::DATAERR;
            }
        }

        // Fetch groups in the semester matching the course pattern
        $groups = Group::find()
            ->innerJoinWith('course')
            ->innerJoinWith('semester')
            ->where("{{%semesters}}.id = $semesterObj->id")
            ->andWhere(['like', '{{%courses}}.name', $course])
            ->all();

        // Fetch the nth task for each group (n = $taskSerial)
        $tasks = array_filter(
            array_map(
                function ($group) use ($taskSerial) {
                /** @var \app\models\Group $group */
                    return $group->getNthTask($taskSerial);
                },
                $groups
            )
        );
        $taskIds = array_map(function ($task) {
            /** @var \app\models\Task $task */
            return $task->id;
        }, $tasks);

        // Fetch all student solutions with non accepted status
        $query = new Query();
        $query->select([
            '{{%users}}.userCode',
            '{{%users}}.name userName',
            '{{%courses}}.name courseName',
            '{{%courses}}.code courseCode',
            '{{%groups}}.number groupNumber',
            '{{%tasks}}.name taskName',
            '{{%student_files}}.isAccepted'
        ])
            ->from('{{%users}}')
            ->innerJoin('{{%subscriptions}}', '{{%subscriptions}}.userID = {{%users}}.id')
            ->innerJoin('{{%groups}}', '{{%groups}}.id = {{%subscriptions}}.groupID')
            ->innerJoin('{{%courses}}', '{{%courses}}.id = {{%groups}}.courseID')
            ->innerJoin('{{%tasks}}', '{{%tasks}}.groupID = {{%groups}}.id')
            ->innerJoin('{{%semesters}}', '{{%semesters}}.id = {{%tasks}}.semesterID')
            ->leftJoin(
                '{{%student_files}}',
                '{{%student_files}}.taskID = {{%tasks}}.id AND {{%student_files}}.uploaderID = {{%users}}.id'
            )
            ->where("{{%semesters}}.id = $semesterObj->id")
            ->andWhere(['like', '{{%courses}}.name', $course])
            ->andWhere(['between', '{{%groups}}.number', $group[0], $group[1]])
            ->andWhere(['in', '{{%tasks}}.id', $taskIds])
            ->andWhere([
                'or',
                "{{%student_files}}.isAccepted <> '" . StudentFile::IS_ACCEPTED_ACCEPTED . "'",
                "{{%student_files}}.isAccepted IS NULL"
            ])
            ->orderBy('userName');
        $results = $query->all();

        // Show data
        $table = new Table();
        $table->setHeaders(['User Code', 'Name', 'Course', 'Task', 'Status']);

        $rows = [];
        foreach ($results as $result) {
            $rows[] = [
                $result['userCode'],
                $result['userName'],
                $result['courseCode'] . '/' . $result['groupNumber'],
                $result['taskName'],
                !empty($result['isAccepted']) ? $result['isAccepted'] : 'Non submitted',
            ];
        }
        echo $table->setRows($rows)
            ->run();

        // E-mail notification
        if (!empty($this->email) && filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $sendResult = Yii::$app->mailer->compose('report/nonAccepted', [
                'courseArg' => $course,
                'taskArg' => $taskSerial,
                'semester' => $semesterObj,
                'results' => $results,
            ])
                ->setFrom(Yii::$app->params['systemEmail'])
                ->setTo($this->email)
                ->setSubject('Jelentés: nem elfogadott beadandók')
                ->send();

            if ($sendResult) {
                $this->stdout("Email has been sent." . PHP_EOL, Console::FG_GREEN);
            } else {
                $this->stdout("Email could not been sent." . PHP_EOL, Console::FG_YELLOW);
            }
        }

        return ExitCode::OK;
    }
}
