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
MailHtml::table(
    [Html::encode($group->course->name), Html::encode($group->number)],
    [Yii::t('app/mail', 'Course'),Yii::t('app/mail', 'Group Number')]
)
?>
<?php
$errorMassages = $group->canvasErrors ? explode(PHP_EOL, $group->canvasErrors) : [];
?>
<?=
MailHtml::table(
    $errorMassages,
    [],
    [
        "textAlign" => "left"
    ]
);
?>
