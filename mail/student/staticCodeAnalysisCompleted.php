<?php

use app\models\CodeCheckerResult;
use app\models\Submission;
use app\mail\layouts\MailHtml;
use yii\helpers\Html;
use yii\mail\BaseMessage;
use yii\web\View;

/* @var $this View View component instance */
/* @var $message BaseMessage Instance of newly created mail message */

/* @var $submission Submission The student solution analyzed */

$task = $submission->task;
$group = $task->group;
$codeCheckerResult = $submission->codeCheckerResult;
?>

<h2><?= Yii::t('app/mail', 'Static code analysis complete') ?></h2>
<?=
MailHtml::p(
    Yii::t('app/mail', 'Static code analysis on your previously submitted solution is complete.')
)
?>
<?php
$tableData = [
    Html::encode($group->course->name),
    Html::a(Html::encode($task->name), Yii::$app->params['frontendUrl'] . '/student/task-manager/tasks/' . $task->id)
];

$tableHeaders = [
    Yii::t('app/mail', 'Course'),
    Yii::t('app/mail', 'Task name')
];

if (!empty($group->number) && !$group->isExamGroup) {
    array_splice($tableData, 1, 0, [$group->number]);
    array_splice($tableHeaders, 1, 0, [Yii::t('app/mail', 'group')]);
}
?>
<?=
MailHtml::table(
    $tableData,
    $tableHeaders
);
?>

<?=
MailHtml::p(
    Yii::t('app/mail', 'Reports')
)
?>
<?php if ($codeCheckerResult->status === CodeCheckerResult::STATUS_NO_ISSUES) : ?>
    <?=
    MailHtml::table(
        [Yii::t('app/mail', 'No issues were found in the uploaded submission.')]
    )
    ?>
<?php elseif ($codeCheckerResult->status === CodeCheckerResult::STATUS_RUNNER_ERROR) : ?>
    <?=
    MailHtml::table(
        [Yii::t('app/mail', 'The static analyzer tool failed to run. The uploaded solution may be incorrect or the configuration for the task may be invalid.')]
    )
    ?>
<?php elseif ($codeCheckerResult->status === CodeCheckerResult::STATUS_RUNNER_ERROR) : ?>
    <?=
    MailHtml::table(
        [Yii::t('app/mail', 'Runner Error')]
    )
    ?>
<?php else : ?>
    <?php foreach ($codeCheckerResult->codeCheckerReports as $report) : ?>
        <?=
        MailHtml::table(
            [
                "$report->filePath ($report->line, $report->column)",
                $report->checkerName,
                $report->severity,
                $report->category,
                $report->message
            ],
            [
                Yii::t('app/mail', 'File (line, column)'),
                Yii::t('app/mail', 'Checker'),
                Yii::t('app/mail', 'Severity'),
                Yii::t('app/mail', 'Category'),
                Yii::t('app/mail', 'Message')
            ]
        )
        ?>
    <?php endforeach; ?>
<?php endif; ?>
