<?php

namespace app\components;

use app\models\StudentFile;
use app\models\Subscription;
use app\models\Task;
use Yii;
use app\models\User;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use Cz\Git\GitRepository;
use ZipArchive;

/**
 * This class contains methods for git integration
 */
class GitManager
{
    /**
     * Create a repository for each student who are assigned to the task
     * @param Task $task is the task
     * @param User $student is the student
     */
    public static function createRepositories($task, $student)
    {
        $id = $task->id;
        $neptun = strtolower($student->neptun);
        if (!is_dir(Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/' . $id . '/' . $neptun . '/')) {
            // Set uniqe string to prevent students to clone other repositories
            $randstring = 'w' . substr(str_shuffle(MD5(microtime())), 0, 25);
            $randstring2 = 'r' . substr(str_shuffle(MD5(microtime())), 0, 25);
            $repopath = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/' . $id . '/' . $neptun . '/' . $randstring . '/';
            $reposym = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/' . $id . '/' . $neptun . '/' . $randstring2;
            if (!is_dir($repopath . '.git')) {
                $repo = GitRepository::init($repopath);
                // Create a symlink for instructors
                if (PHP_OS_FAMILY === "Windows") {
                    // relative paths on windows are not supported for symlinks
                    symlink($repopath, $reposym);
                } else {
                    symlink($randstring, $reposym);
                }
                // Create the appropriate settings for the repo
                $repo->execute(array('config', 'receive.denyNonFastForwards', 'true'));
                $repo->execute(array('config', 'receive.denyDeletes', 'true'));
                $repo->execute(array('config', 'receive.denyCurrentBranch', 'updateInstead'));
                $repo->execute(array('config', 'user.name', $student->name ?? $student->neptun));
                $repo->execute(array('config', 'user.email', $student->email ?? '<>'));
                // Create pre-receive git hook
                $a = rename($repopath . '.git/hooks/pre-receive.sample', $repopath . '.git/hooks/pre-receive');
                $originalLanguage = Yii::$app->language;
                Yii::$app->language = $student->locale;
                if ($a) {
                    $prerecievehook = fopen($repopath . '.git/hooks/pre-receive', 'w');
                    self::writePreRecieveGitHook($prerecievehook, $task->hardDeadline, $task->passwordProtected);
                    fclose($prerecievehook);
                }
                // Create pre-receive git hook
                $postrecievehook = fopen($repopath . '.git/hooks/post-receive', 'w');
                self::writePostRecieveGitHook($postrecievehook, $id, $student->id);
                fclose($postrecievehook);
                chmod($repopath . '.git/hooks/post-receive', 0755);
                Yii::$app->language = $originalLanguage;
            }
        }
    }

    /**
     * Write git pre-receive git hook to prevent creating new branches on repo and to prevent pushing solutions after the deadline
     * @param resource $postrecievehook is the githook file
     * @param int $taskid is the id of the task
     * @param int $studentid is the id of the student
     */
    public static function writePostRecieveGitHook($postrecievehook, $taskid, $studentid)
    {
        $hook = Yii::$app->params['versionControl']['shell'] . "
curl --request GET --url \"" . Yii::$app->request->hostInfo . Yii::$app->params['versionControl']['basePath'] . "/git-push?taskid=" . $taskid . "&studentid=" . $studentid . "\" --location";

        fwrite($postrecievehook, $hook);
    }

    /**
     * Write git pre-receive git hook to prevent creating new branches on repo and to prevent pushing solutions after the deadline or to password protected tasks
     * @param resource $prerecievehook is the githook file
     * @param string $hardDeadline is the deadline of the task
     * @param bool $isPasswordProtected is the task password protected
     * @param bool $isAccepted is the status of the student submission
     */
    public static function writePreRecieveGitHook($prerecievehook, $hardDeadline, $isPasswordProtected = false, $isAccepted = false)
    {
        $hook = Yii::$app->params['versionControl']['shell'] . "
rc=0
while read old new refname; do
    if [[ \$refname != refs/heads/master ]]; then
        if [[ \$refname == refs/heads/* && \$old != *[^0]* ]]; then
                rc=1
                echo \"" . Yii::t('app', 'Refusing to create new branch: ') . "\$refname\"
        fi
    fi
done";
        if ($isAccepted) {
            $hook .= "
echo \"" . Yii::t('app', 'Your solution was accepted!') . "\"
exit 1";
        } elseif ($isPasswordProtected) {
            $hook .= "
echo \"" . Yii::t('app', "You cannot use 'git push' command for password protected tasks. Use the web interface to upload new solution!") . "\"
exit 1";
        } else {
            $hook .= "
currTime=`date +\"%Y-%m-%d %H:%m:%M\"`
harddeadline='" . $hardDeadline . "'
if [[ \"\$currTime\" > \"\$harddeadline\" ]]; then
     rc=1
     echo \"" . Yii::t('app', 'The deadline expired') . "\"
fi
exit \$rc";
        }

        fwrite($prerecievehook, $hook);
    }

    /**
     * @param Task $task
     * @param Subscription $subscription
     */
    public static function afterTaskUpdate($task, $subscription)
    {
        $repopath = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/' . $task->id . '/' . strtolower($subscription->user->neptun) . '/';
        $dirs = FileHelper::findDirectories($repopath, ['recursive' => false]);
        rsort($dirs);
        $repopath = $repopath . basename($dirs[0]) . '/';
        $hookfile = fopen($repopath . '.git/hooks/pre-receive', "w");
        $studentFile = StudentFile::findOne(['taskID' => $task->id, 'uploaderID' => $subscription->userID]);
        self::writePreRecieveGitHook($hookfile, $task->hardDeadline, $task->passwordProtected, $studentFile != null && $studentFile->isAccepted == StudentFile::IS_ACCEPTED_ACCEPTED);
        fclose($hookfile);
    }

    /**
     * @param StudentFile $studentFile
     */
    public static function afterStatusUpdate($studentFile)
    {
        $repopath = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/' . $studentFile->taskID . '/' . strtolower($studentFile->uploader->neptun) . '/';
        $dirs = FileHelper::findDirectories($repopath, ['recursive' => false]);
        rsort($dirs);
        $repopath = $repopath . basename($dirs[0]) . '/';
        $hookfile = fopen($repopath . '.git/hooks/pre-receive', "w");
        self::writePreRecieveGitHook($hookfile, $studentFile->task->hardDeadline, $studentFile->task->passwordProtected, $studentFile->isAccepted == StudentFile::IS_ACCEPTED_ACCEPTED);
        fclose($hookfile);
    }

    /**
     * Creates Zip file from the solution
     * @param int $taskid is the id of the task
     * @param int $studentid is the id of the student
     */
    public static function createZip($taskid, $studentid)
    {
        // Find the unique string
        $student = User::findOne($studentid);
        $basepath = Yii::$app->basePath . '/' . Yii::$app->params['data_dir'] . '/uploadedfiles/' .  $taskid .  '/' . $student->neptun . '/';
        $dirs = FileHelper::findDirectories($basepath, ['recursive' => false]);
        rsort($dirs);
        $repopath = $basepath .  basename($dirs[0]) . '/';
        // Find all files and directories
        $files = FileHelper::findFiles($repopath, ['except' => ['*.git*']]);
        $directories = FileHelper::findDirectories($repopath, ['except' => ['.git/']]);
        // Zip every file
        $zip = new ZipArchive();
        $res = $zip->open($basepath . '/' . $student->neptun . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($res) {
            $anyfile = false;

            foreach ($directories as $d) {
                $location = substr($d, strlen($repopath));
                $anyfile = $zip->addEmptyDir($location);
            }
            foreach ($files as $f) {
                $location = substr($f, strlen($repopath));
                $zip->addFile($f, $location);
                $anyfile = $zip->addFile($f, $location);
            }
            if (!$anyfile) {
                $zip->addEmptyDir("empty");
            }
            $zip->close();
        }
    }

    /**
     * Commit new submission
     * @param string $repopath
     * @param string $zipPath
     * @throws \Cz\Git\GitException
     */
    public static function uploadToRepo($repopath, $zipPath)
    {
        $repo = new GitRepository("$repopath.git");

        // Delete all files and directories from repo
        $oldfiles = FileHelper::findFiles($repopath, ['except' => ['.git*']]);
        $olddirectories = FileHelper::findDirectories($repopath, ['except' => ['.git/']]);
        rsort($olddirectories);
        foreach ($oldfiles as $of) {
            unlink($of);
        }
        foreach ($olddirectories as $od) {
            rmdir($od);
        }

        // Add changes to the repo
        $zip = new ZipArchive();
        $res = $zip->open($zipPath);
        if ($res) {
            $zip->extractTo($repopath);
            $zip->close();
        }

        if ($repo->hasChanges()) {
            $repo->addAllChanges();
            $repo->commit(Yii::t('app', 'Submission from the web user interface'));
        }
    }
}
