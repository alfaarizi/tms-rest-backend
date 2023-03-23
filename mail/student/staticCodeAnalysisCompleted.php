<?php

use app\models\CodeCheckerResult;
use app\models\StudentFile;
use yii\helpers\Html;
use yii\mail\BaseMessage;
use yii\web\View;

/* @var $this View View component instance */
/* @var $message BaseMessage Instance of newly created mail message */

/* @var $studentFile StudentFile The student solution analyzed */

$task = $studentFile->task;
$group = $task->group;
$codeCheckerResult = $studentFile->codeCheckerResult;
?>

<h2><?= \Yii::t('app/mail', 'Static code analysis ready') ?></h2>
<p>
    <?= \Yii::t('app/mail', 'Static code analysis on your previously submitted solution is ready.') ?><br>
    <?= \Yii::t('app/mail', 'Course') ?>: <?= Html::encode($group->course->name) ?>
    <?php if (!empty($group->number) && !$group->isExamGroup) : ?>
        (<?= \Yii::t('app/mail', 'group') ?>: <?= $group->number ?>)
    <?php endif; ?>
    <br>
    <?= \Yii::t('app/mail', 'Task name') ?>:
    <?= Html::a(Html::encode($task->name), Yii::$app->params['frontendUrl'] . '/student/task-manager/tasks/' . $task->id) ?>
</p>

<h3><?= \Yii::t('app/mail', 'Reports') ?></h3>
<?php if ($codeCheckerResult->status === CodeCheckerResult::STATUS_NO_ISSUES) : ?>
    <p><?= \Yii::t('app/mail', 'No issues were found in the uploaded submission.') ?></p>
<?php elseif ($codeCheckerResult->status === CodeCheckerResult::STATUS_RUNNER_ERROR) : ?>
    <p><?= \Yii::t('app/mail', 'The static analyzer tool failed to run. The uploaded solution may be incorrect or the configuration for the task may be invalid.') ?></p>
<?php elseif ($codeCheckerResult->status === CodeCheckerResult::STATUS_RUNNER_ERROR) : ?>
    <p><?= \Yii::t('app/mail', 'Runner Error') ?></p>
<?php else : ?>
    <ul>
        <?php foreach ($codeCheckerResult->codeCheckerReports as $report) : ?>
            <li>
                <strong><?= \Yii::t('app/mail', 'File (line, column)') ?>:</strong> <?= "$report->filePath ($report->line, $report->column)" ?><br>
                <strong><?= \Yii::t('app/mail', 'Checker') ?>:</strong> <?= $report->checkerName ?><br>
                <strong><?= \Yii::t('app/mail', 'Severity') ?>:</strong> <?= $report->severity ?><br>
                <strong><?= \Yii::t('app/mail', 'Category') ?>:</strong> <?= $report->category ?><br>
                <strong><?= \Yii::t('app/mail', 'Message') ?>:</strong> <?= $report->message ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
