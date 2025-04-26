<?php

use app\models\Submission;
use app\mail\layouts\MailHtml;
use yii\helpers\Html;
use yii\mail\BaseMessage;
use yii\web\View;

/* @var $this View view component instance */
/* @var $message BaseMessage instance of newly created mail message */
/* @var $solutions Submission[] The new student solution submitted */
/* @var $hours integer The digest interval */
?>

<h2><?= Yii::t('app/mail', 'Submitted solutions') ?></h2>
<?=
MailHtml::p(
    Yii::t('app/mail', 'Student solutions submitted in the past {hours} hours', ['hours' => $hours])
)
?>
<?php foreach ($solutions as $solution) : ?>
    <?php
    $tableData = [
        Html::encode($solution->uploader->name) . ' ' . (Html::encode($solution->uploader->userCode)),
        Html::encode($solution->task->group->course->name),
        Html::encode($solution->task->name)
    ];

    $tableHeaders = [
        Yii::t('app/mail', 'Name'),
        Yii::t('app/mail', 'Course'),
        Yii::t('app/mail', 'Task name')
    ];

    if (!empty($solution->task->group->number)) {
        array_splice($tableData, 2, 0, [$solution->task->group->number]);
        array_splice($tableHeaders, 2, 0, [Yii::t('app/mail', 'group')]);
    }

    if ($solution->status == Submission::STATUS_CORRUPTED) {
        $tableData[] = '<span style="color: #dc4126;">' . Yii::t('app/mail', 'Corrupted') . '</span>';
    }

    $tableData[] = Html::a(
        Yii::t('app/mail', 'View solution'),
        Yii::$app->params['frontendUrl'] . '/instructor/task-manager/submissions/' . $solution->id
    )
    ?>
    <?=
    MailHtml::table(
        $tableData,
        $tableHeaders
    )
    ?>
<?php endforeach; ?>
<?=
MailHtml::p(
    Yii::t('app/mail', 'The list does not contain the already graded solutions.')
)
?>
