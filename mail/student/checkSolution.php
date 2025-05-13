<?php

use app\models\Submission;
use app\mail\layouts\MailHtml;
use yii\helpers\Html;
use yii\mail\BaseMessage;
use yii\web\View;

/* @var $this View view component instance */
/* @var $message BaseMessage instance of newly created mail message */
/* @var $submission Submission The student solution tested */

$task = $submission->task;
$group = $task->group;
?>

<h2><?= Yii::t('app/mail', 'Automated submission test completed') ?></h2>
<?=
MailHtml::p(
    Yii::t('app/mail', 'Automated testing on your previously submitted solution is complete.')
);
?>
<?=
MailHtml::table([
    ['th' => Yii::t('app/mail', 'Course'), 'td' => Html::encode($group->course->name)],
    ['th' => Yii::t('app/mail', 'group'), 'td' => (!empty($group->number) && !$group->isExamGroup) ? $group->number : ''],
    ['th' => Yii::t('app/mail', 'Task name'), 'td' => Html::a(Html::encode($task->name), Yii::$app->params['frontendUrl'] . '/student/task-manager/tasks/' . $task->id)],
    ['th' => Yii::t('app/mail', 'Result'), 'td' => Yii::t('app', $submission->status)],
    ['th' => Yii::t('app/mail', 'Remark'), 'td' => Html::encode($submission->safeErrorMsg)],
])
?>
