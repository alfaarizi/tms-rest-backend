<?php

use app\models\Submission;
use app\mail\layouts\MailHtml;
use yii\helpers\Html;
use yii\mail\BaseMessage;
use yii\web\View;

/* @var $this View view component instance */
/* @var $message BaseMessage instance of newly created mail message */
/* @var $actor \app\models\User The actor of the action */
/* @var $submission Submission The student solution graded */

$group = $submission->task->group;
?>

<?php
$tableData = [
    Html::encode($group->course->name),
    Yii::t('app', $submission->status),
    $submission->grade,
    Html::encode(nl2br($submission->notes, false))
];

$tableHeaders = [
    Yii::t('app/mail', 'Course'),
    Yii::t('app/mail', 'Status'),
    Yii::t('app/mail', 'Grade'),
    Yii::t('app/mail', 'Remark')
];

if (!empty($group->number) && !$group->isExamGroup) {
    array_splice($tableData, 1, 0, [$group->number]);
    array_splice($tableHeaders, 1, 0, [Yii::t('app/mail', 'group')]);
}

if (!$group->isExamGroup) {
    $tableData[] = Html::encode($actor->name);
    $tableHeaders[] = Yii::t('app/mail', 'Modifier');
}
?>

<h2><?= Yii::t('app/mail', 'Graded submission') ?></h2>
<?=
MailHtml::p(
    Yii::t('app/mail', 'New grade for previous submission has been recorded.')
)
?>
<?=
MailHtml::table(
    $tableData,
    $tableHeaders
)
?>
