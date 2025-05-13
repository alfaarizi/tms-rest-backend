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
<?=
MailHtml::table([
    [
        'th' => Yii::t('app/mail', 'Course'),
        'td' => Html::encode($group->course->name)
    ],
    [
        'th' => Yii::t('app/mail', 'group'),
        'td' => $group->number,
        'cond' => (!empty($group->number) && !$group->isExamGroup)
    ],
    [
        'th' => Yii::t('app/mail', 'Task name'),
        'td' => Html::a(Html::encode($task->name), Yii::$app->params['frontendUrl'] . '/student/task-manager/tasks/' . $task->id)
    ]
])
?>

<?=
MailHtml::p(
    Yii::t('app/mail', 'Reports')
)
?>
<?php if ($codeCheckerResult->status === CodeCheckerResult::STATUS_NO_ISSUES) : ?>
    <?=
    MailHtml::table([
        ['td' => Yii::t('app/mail', 'No issues were found in the uploaded submission.')]
    ])
    ?>
<?php elseif ($codeCheckerResult->status === CodeCheckerResult::STATUS_RUNNER_ERROR) : ?>
    <?=
    MailHtml::table([
        ['td' => Yii::t('app/mail', 'The static analyzer tool failed to run. The uploaded solution may be incorrect or the configuration for the task may be invalid.')]
    ])
    ?>
<?php elseif ($codeCheckerResult->status === CodeCheckerResult::STATUS_RUNNER_ERROR) : ?>
    <?=
    MailHtml::table([
        ['td' => Yii::t('app/mail', 'Runner Error')]
    ])
    ?>
<?php else : ?>
    <?php foreach ($codeCheckerResult->codeCheckerReports as $report) : ?>
        <?=
        MailHtml::table([
            [
                'th' => Yii::t('app/mail', 'File (line, column)'),
                'td' => "$report->filePath ($report->line, $report->column)"
            ],
            [
                'th' => Yii::t('app/mail', 'Checker'),
                'td' => $report->checkerName
            ],
            [
                'th' => Yii::t('app/mail', 'Severity'),
                'td' =>  $report->severity
            ],
            [
                'th' => Yii::t('app/mail', 'Category'),
                'td' => $report->category
            ],
            [
                'th' => Yii::t('app/mail', 'Message'),
                'td' => $report->message
            ]
        ])
        ?>
    <?php endforeach; ?>
<?php endif; ?>
