<?php

namespace app\components;

use app\models\Submission;
use app\models\Subscription;
use app\models\Task;
use app\models\User;
use CzProject\GitPhp\GitException;
use CzProject\GitPhp\Git;
use Yii;
use yii\base\ErrorException;
use yii\helpers\FileHelper;
use yii\helpers\Url;
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
     * @throws GitException
     */
    public static function createUserRepository(Task $task, User $student): void
    {
        $id = $task->id;
        $userCode = strtolower($student->userCode);
        if (!is_dir(Yii::getAlias("@appdata/uploadedfiles/$id/$userCode/"))) {
            // Set uniqe string to prevent students to clone other repositories
            $randstring = 'w' . substr(str_shuffle(MD5(microtime())), 0, 25);
            $randstring2 = 'r' . substr(str_shuffle(MD5(microtime())), 0, 25);
            $repopath = Yii::getAlias("@appdata/uploadedfiles/$id/$userCode/$randstring/");
            $reposym = Yii::getAlias("@appdata/uploadedfiles/$id/$userCode/$randstring2");
            if (!is_dir($repopath . '.git')) {
                $git = new Git();

                $repo = $git->init($repopath);
                // Create a symlink for instructors
                if (PHP_OS_FAMILY === "Windows") {
                    // relative paths on windows are not supported for symlinks
                    symlink($repopath, $reposym);
                } else {
                    symlink($randstring, $reposym);
                }
                // Create the appropriate settings for the repo
                $repo->execute('config', 'receive.denyNonFastForwards', 'true');
                $repo->execute('config', 'receive.denyDeletes', 'true');
                $repo->execute('config', 'receive.denyCurrentBranch', 'updateInstead');
                $repo->execute('config', 'user.name', $student->name ?? $student->userCode);
                $repo->execute('config', 'user.email', $student->email ?? '<>');
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

                // Initial commit (this is required for subrepos)
                $repo->commit(Yii::t('app', 'Repository created'), ['--allow-empty']);
                Yii::$app->language = $originalLanguage;
            }
        }
        self::addUserSubModuleToTaskRepository($id, $userCode);
    }

    /**
     * Create a common repository for the given task.
     * @throws GitException
     */
    public static function createTaskLevelRepository(int $taskId): void
    {
        $randstring = 'r' . substr(str_shuffle(MD5(microtime())), 0, 25);
        $repopath = Yii::getAlias("@appdata/uploadedfiles/$taskId/all/$randstring/");
        FileHelper::createDirectory($repopath, 0775, true);

        $git = new Git();
        $repo = $git->init($repopath);
        // Create the appropriate settings for the repo
        $repo->execute('config', 'user.name', 'TMS');
        $repo->execute('config', 'user.email', Yii::$app->params['systemEmail']);
        $repo->commit(Yii::t('app', 'Repository created'), ['--allow-empty']);
    }

    /**
     * Add the user repository to the common task repository as a git submodule
     * @throws GitException
     */
    private static function addUserSubModuleToTaskRepository(int $taskID, string $userCode): void
    {
        $userCode = strtolower($userCode);
        $taskRepoPath = self::getTaskLevelRepoDirectoryPath($taskID);

        if ($taskRepoPath === null) {
            return;
        }

        $git = new Git();
        $taskRepo = $git->open($taskRepoPath);
        $taskRepo->execute('submodule', 'add', self::getReadonlyUserRepositoryUrl($taskID, $userCode), $userCode);
        $taskRepo->addAllChanges();
        $taskRepo->commit(Yii::t('app', 'Added git submodule: {repoName}', ['repoName' => $userCode]));
    }

    /**
     * Remove the user repository submodule from the task repository
     * @throws GitException
     * @throws ErrorException thrown when the submodule directory cannot be removed
     */
    public static function removeUserFromTaskRepository(int $taskID, string $userCode): void
    {
        $userCode = strtolower($userCode);
        $taskRepoPath = self::getTaskLevelRepoDirectoryPath($taskID);

        if ($taskRepoPath === null) {
            return;
        }

        $git = new Git();
        $taskRepo = $git->open($taskRepoPath);
        $taskRepo->execute('submodule', 'deinit', $userCode);
        $taskRepo->execute('rm', '-f', $userCode);
        $taskRepo->addAllChanges();
        $taskRepo->commit(Yii::t('app', 'Removed git submodule: {repoName}', ['repoName' => $userCode]));
        FileHelper::removeDirectory(self::getTaskLevelRepoDirectoryPath($taskID) . '/.git/modules/' . $userCode);
    }

    /**
     * Get task level repository directory path
     * Returns null if the task does not have a task-level repository.
     */
    private static function getTaskLevelRepoDirectoryPath(int $taskID): ?string
    {
        $taskAllDirPath = Yii::getAlias("@appdata/uploadedfiles/$taskID/all");

        if (!is_dir($taskAllDirPath)) {
            return null;
        }

        return FileHelper::findDirectories($taskAllDirPath, ['recursive' => false])[0];
    }

    /**
     * Gets read-only task-level repository address for the given task.
     * Returns null if the task does not have a task-level repository.
     */
    public static function getReadonlyTaskLevelRepositoryUrl(int $taskID): ?string
    {
        $taskRepoPath = self::getTaskLevelRepoDirectoryPath($taskID);
        if ($taskRepoPath === null) {
            return null;
        }

        return Yii::$app->request->hostInfo . Yii::$app->params['versionControl']['basePath']
            . '/' . $taskID . '/all/' . basename($taskRepoPath);
    }

    /**
     * Get read-only repository address for the given task and user pair
     */
    public static function getReadonlyUserRepositoryUrl(int $taskID, string $userCode): string
    {
        $userCode = strtolower($userCode);
        $userRepoPath = Yii::getAlias("@appdata/uploadedfiles/$taskID/$userCode/");
        // Search for random string id directory
        $dirs = FileHelper::findDirectories($userRepoPath, ['recursive' => false]);
        rsort($dirs);
        return Yii::$app->request->hostInfo . Yii::$app->params['versionControl']['basePath'] . '/'
            . $taskID . '/' . $userCode . '/' . basename($dirs[1]);
    }

    /**
     * Get writeable repository address for the given task and user pair
     */
    public static function getWriteableUserRepositoryUrl(int $taskID, string $userCode): string
    {
        $userCode = strtolower($userCode);
        $path = Yii::getAlias("@appdata/uploadedfiles/$taskID/$userCode/");
        // Search for random string id directory
        $dirs = FileHelper::findDirectories($path, ['recursive' => false]);
        rsort($dirs);
        return Yii::$app->request->hostInfo . Yii::$app->params['versionControl']['basePath'] . '/'
            . $taskID . '/' . $userCode . '/' . basename($dirs[0]);
    }

    /**
     * Write git pre-receive git hook to prevent creating new branches on repo and to prevent pushing solutions after the deadline
     * @param resource $postrecievehook is the githook file
     * @param int $taskid is the id of the task
     * @param int $studentid is the id of the student
     */
    public static function writePostRecieveGitHook($postrecievehook, int $taskid, int $studentid): void
    {
        $hook = Yii::$app->params['versionControl']['shell'] . "
curl --request GET --url \"" . Url::toRoute(['/git/git-push', 'taskid' => $taskid, 'studentid' =>  $studentid], true) . "\" --location";

        fwrite($postrecievehook, $hook);
    }

    /**
     * Write git pre-receive git hook to prevent creating new branches on repo and to prevent pushing solutions after the deadline or to password protected tasks
     * @param resource $prerecievehook is the githook file
     * @param string $hardDeadline is the deadline of the task
     * @param bool $isPasswordProtected is the task password protected
     * @param bool $isAccepted is the acceptance status of the student submission
     */
    public static function writePreRecieveGitHook($prerecievehook, string $hardDeadline, bool $isPasswordProtected = false, bool $isAccepted = false): void
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
     * Modifies pre-receive hooks after task update
     */
    public static function afterTaskUpdate(Task $task, Subscription $subscription): void
    {
        $repopath = Yii::getAlias("@appdata/uploadedfiles/") . $task->id . '/'
            . strtolower($subscription->user->userCode) . '/';
        $dirs = FileHelper::findDirectories($repopath, ['recursive' => false]);
        rsort($dirs);
        $repopath = $repopath . basename($dirs[0]) . '/';
        $hookfile = fopen($repopath . '.git/hooks/pre-receive', "w");
        $submission = Submission::findOne(['taskID' => $task->id, 'uploaderID' => $subscription->userID]);
        self::writePreRecieveGitHook(
            $hookfile,
            $task->hardDeadline,
            $task->passwordProtected,
            $submission != null && $submission->status == Submission::STATUS_ACCEPTED
        );
        fclose($hookfile);
    }

    /**
     * Triggers pre-receive hook update after task update
     */
    public static function afterStatusUpdate(Submission $submission): void
    {
        $repopath = Yii::getAlias("@appdata/uploadedfiles/") . $submission->taskID . '/'
            . strtolower($submission->uploader->userCode) . '/';
        $dirs = FileHelper::findDirectories($repopath, ['recursive' => false]);
        rsort($dirs);
        $repopath = $repopath . basename($dirs[0]) . '/';
        $hookfile = fopen($repopath . '.git/hooks/pre-receive', "w");
        self::writePreRecieveGitHook(
            $hookfile,
            $submission->task->hardDeadline,
            $submission->task->passwordProtected,
            $submission->status == Submission::STATUS_ACCEPTED
        );
        fclose($hookfile);
    }

    /**
     * Creates Zip file from the solution
     * @param int $taskid is the id of the task
     * @param int $studentid is the id of the student
     */
    public static function createZip(int $taskid, int $studentid): void
    {
        // Find the unique string
        $student = User::findOne($studentid);
        $basepath = Yii::getAlias("@appdata/uploadedfiles/$taskid/") . $student->userCode . '/';
        $dirs = FileHelper::findDirectories($basepath, ['recursive' => false]);
        rsort($dirs);
        $repopath = $basepath .  basename($dirs[0]) . '/';
        // Find all files and directories
        $files = FileHelper::findFiles($repopath, ['except' => ['*.git*']]);
        $directories = FileHelper::findDirectories($repopath, ['except' => ['.git/']]);
        // Zip every file
        $zip = new ZipArchive();
        $res = $zip->open($basepath . '/' . $student->userCode . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
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
     * @throws GitException
     */
    public static function uploadToRepo(string $repoPath, string $zipPath): void
    {
        $repoPath = realpath($repoPath);
        $zipPath = realpath($zipPath);
        $git = new Git();
        $repo = $git->open("$repoPath/.git");

        // Delete all files and directories from repo
        $oldfiles = FileHelper::findFiles($repoPath, ['except' => ['/.git/']]);
        $olddirectories = FileHelper::findDirectories($repoPath, ['except' => ['/.git/']]);
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
            $zip->extractTo($repoPath);
            $zip->close();
        }

        if ($repo->hasChanges()) {
            $repo->addAllChanges();
            $repo->commit(Yii::t('app', 'Submission from the web user interface'));
        }
    }
}
