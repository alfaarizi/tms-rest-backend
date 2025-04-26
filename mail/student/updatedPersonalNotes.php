<?php

use app\mail\layouts\MailHtml;
use yii\helpers\Html;
use yii\mail\BaseMessage;
use yii\web\View;

/* @var $this View view component instance */
/* @var $message BaseMessage instance of newly created mail message */
/* @var $subscription app\models\Subscription  */

$group = $subscription->group;
$notes = $subscription->notes;
?>

<?php
$tableData = [
    Html::encode($group->course->name),
    Html::encode($notes)
];

$tableHeaders = [
    Yii::t('app/mail', 'Course'),
    Yii::t('app/mail', 'Notes')
];

if (!empty($group->number) && !$group->isExamGroup) {
    array_splice($tableData, 1, 0, [$group->number]);
    array_splice($tableHeaders, 1, 0, [Yii::t('app/mail', 'group')]);
}
?>

<h2><?= Yii::t('app/mail', 'New Notes') ?></h2>
<?=
MailHtml::p(
    Yii::t('app/mail', 'New notes were added')
)
?>
<?=
MailHtml::table(
    $tableData,
    $tableHeaders,
)
?>
