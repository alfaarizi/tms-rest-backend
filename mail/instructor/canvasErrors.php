<?php

use app\models\Group;
use yii\helpers\Html;
use yii\mail\BaseMessage;
use yii\web\View;

/* @var $this View view component instance */
/* @var $message BaseMessage instance of newly created mail message */

/* @var $group Group a canvas group with error messages */
?>

<h2><?= Yii::t('app/mail', 'Canvas synchronization errors') ?></h2>
<p>
    <b><?= Yii::t('app/mail', 'New Canvas synchronization errors have occurred') ?>:</b>
</p>
<p>
    <b><?= \Yii::t('app/mail', 'Course') ?>: <?= Html::encode($group->course->name) ?></b><br>
    <b><?= \Yii::t('app/mail', 'Group Number') ?>: <?= Html::encode($group->number) ?></b>
</p>
<ul>
    <?php foreach (explode(PHP_EOL, $group->canvasErrors) as $errorMessage) : ?>
        <li>
            <?= Html::encode($errorMessage) ?>
        </li>
    <?php endforeach; ?>
</ul>
