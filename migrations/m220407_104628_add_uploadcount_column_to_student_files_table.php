<?php

use Cz\Git\GitRepository;
use yii\db\Migration;
use yii\db\Query;
use yii\helpers\FileHelper;

/**
 * Handles adding uploadCount to table `{{%student_files}}` and calculate values for existing files.
 */
class m220407_104628_add_uploadcount_column_to_student_files_table extends Migration
{
    private const BATCH_SIZE = 1000;

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add new column with value 0
        $this->addColumn(
            '{{%student_files}}',
            'uploadCount',
            $this->integer()->notNull()->defaultValue(1)->after('isAccepted')
        );
        $this->update('{{%student_files}}', ['uploadCount' => 0]);

        // Update values from TMS 1.x logs
        $this->updateFilesFromLogs(
            '#\(\#([0-9]*)\)#',
            'app\controllers\StudentController::actionUploadFile'
        );

        // Update values from TMS 2.x logs
        $this->updateFilesFromLogs(
            '#\(([0-9]*)\)#',
            'app\modules\student\controllers\StudentFilesController::saveFile'
        );

        // Restore upload count from git repository for version controlled tasks
        if (Yii::$app->params['versionControl']['enabled']) {
            $this->updateVersionControlledFiles();
        }

        // Set updated solutions without logs and git repos to 2
        $this->update(
            '{{%student_files}}',
            ['uploadCount' => 2],
            ['uploadCount' => 0, 'isAccepted' => 'Updated']
        );

        // Set all other solution to 1
        $this->update(
            '{{%student_files}}',
            ['uploadCount' => 1],
            ['uploadCount' => 0]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%student_files}}', 'uploadCount');
    }

    private function updateVersionControlledFiles()
    {
        $query = (new Query())
            ->select([
                '{{%student_files}}.taskID',
                '{{%student_files}}.id',
                '{{%users}}.neptun'
            ])
            ->from('{{%student_files}}')
            ->innerJoin('{{%users}}', '{{%users}}.id = {{%student_files}}.uploaderID')
            ->where(['{{%student_files}}.isVersionControlled' => 1]);

        $count = $query->count();

        for ($i = 0; $i <= $count; $i += self::BATCH_SIZE) {
            // Get the current batch
            $files = $query
                ->offset($i)
                ->limit(self::BATCH_SIZE)
                ->all();

            foreach ($files as $file) {
                $basePath = Yii::getAlias("@appdata/uploadedfiles/") . $file['taskID']
                    . '/' . strtolower($file['neptun']) . '/';

                $dirs = FileHelper::findDirectories($basePath, ['recursive' => false]);
                rsort($dirs);
                $repoPath = $basePath . basename($dirs[0]) . '/';
                $repo = new GitRepository("$repoPath.git");

                // check if for some reason there is no commit in the repository
                $lastCommit = $repo->execute(['rev-list', '-n', '1', '--all']);
                if (!empty($lastCommit)) {
                    // get commit count
                    $result = $repo->execute(['rev-list', '--count', 'HEAD']);

                    $this->update('{{%student_files}}', ['uploadCount' => intval($result[0], 10), ['id' => $file['id']]]);
                }
            }
        }
    }


    private function updateFilesFromLogs($taskIDPattern, $logCategory)
    {
        $query = (new Query())
            ->select(['prefix', 'message'])
            ->from('{{%log}}')
            ->where(['category' => $logCategory, 'level' => 4]);

        $count = $query->count();

        for ($i = 0; $i <= $count; $i += self::BATCH_SIZE) {
            // Get the current batch
            $rows = $query
                ->offset($i)
                ->limit(self::BATCH_SIZE)
                ->all();

            foreach ($rows as $row) {
                preg_match('#\((.*?)\)#', $row['prefix'], $neptunMatch);
                $neptun = $neptunMatch[1];

                preg_match_all($taskIDPattern, $row['message'], $taskIDMatches, PREG_SET_ORDER);
                $taskID = end($taskIDMatches)[1];

                $user = (new Query())
                    ->select('id')
                    ->from('{{%users}}')
                    ->where(['neptun' => $neptun])
                    ->one();
                $file = (new Query())
                    ->select(['isVersionControlled', 'uploadCount', 'id'])
                    ->from('{{%student_files}}')
                    ->where(['uploaderID' => $user['id'], 'taskID' => $taskID])
                    ->one();

                // $file should exist, but is could be missing in case of manual deletion
                if (!is_null($file) && !$file['isVersionControlled']) {
                    $this->update('{{%student_files}}', ['uploadCount' => $file['uploadCount'] + 1], ['id' => $file['id']]);
                }
            }
        }
    }
}
