<?php

use app\models\Group;
use app\mail\layouts\MailHtml;
use yii\helpers\Html;
use yii\mail\BaseMessage;
use yii\web\View;

/* @var $this View view component instance */
/* @var $message BaseMessage instance of newly created mail message */
/* @var $group Group a canvas group with error messages */
?>

<h2><?= Yii::t('app/mail', 'Canvas synchronization errors') ?></h2>
<?=
MailHtml::p(
    Yii::t('app/mail', 'New Canvas synchronization errors have occurred')
);
?>
<?=
MailHtml::table([
    ['th' => Yii::t('app/mail', 'Course'), 'td' => Html::encode($group->course->name)],
    ['th' => Yii::t('app/mail', 'Group Number'), 'td' => Html::encode($group->number)]
])
?>
<?php
$errorMassages = $group->canvasErrors ? explode(PHP_EOL, $group->canvasErrors) : [];

$errorRows = array_map(function ($error) {
    return ['td' => Html::encode($error)];
}, $errorMassages);

if (!empty($errorRows)) {
    echo MailHtml::table($errorRows, [
        'textAlign' => 'left',
    ]);
}
?>
