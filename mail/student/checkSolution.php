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

<?php
$tableData = [
    Html::encode($group->course->name),
    Html::a(Html::encode($task->name), Yii::$app->params['frontendUrl'] . '/student/task-manager/tasks/' . $task->id),
    Yii::t('app', $submission->status),
    Html::encode($submission->safeErrorMsg)
];

$tableHeaders = [
    Yii::t('app/mail', 'Course'),
    Yii::t('app/mail', 'Task name'),
    Yii::t('app/mail', 'Result'),
    Yii::t('app/mail', 'Remark')
];

if (!empty($group->number) && !$group->isExamGroup) {
    array_splice($tableData, 1, 0, [$group->number]);
    array_splice($tableHeaders, 1, 0, [Yii::t('app/mail', 'group')]);
}
?>

<h2><?= Yii::t('app/mail', 'Automated submission test completed') ?></h2>
<?=
MailHtml::p(
    Yii::t('app/mail', 'Automated testing on your previously submitted solution is complete.')
);
?>
<?=
MailHtml::table(
    $tableData,
    $tableHeaders
);
?>
