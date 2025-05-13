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
    <?=
    MailHtml::table([
        ['th' => Yii::t('app/mail', 'Name'), 'td' => Html::encode("{$solution->uploader->name} ({$solution->uploader->userCode})")],
        ['th' => Yii::t('app/mail', 'Course'), 'td' => Html::encode($solution->task->group->course->name)],
        ['th' => Yii::t('app/mail', 'group'), 'td' => !empty($solution->task->group->number) ? $solution->task->group->number : ''],
        ['th' => Yii::t('app/mail', 'Task name'), 'td' => Html::encode($solution->task->name)],
        ['th' => Yii::t('app/mail', 'Status'), 'td' => $solution->status == Submission::STATUS_CORRUPTED ? Yii::t('app/mail', 'Corrupted') : ''],
        ['th' => Yii::t('app/mail', 'View solution'), 'td' => Html::a(
            Yii::t('app/mail', 'View solution'),
            Yii::$app->params['frontendUrl'] . '/instructor/task-manager/submissions/' . $solution->id
        )]
    ])
    ?>
<?php endforeach; ?>
<?=
MailHtml::p(
    Yii::t('app/mail', 'The list does not contain the already graded solutions.')
)
?>
