<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View view component instance */
/* @var $message \yii\mail\BaseMessage instance of newly created mail message */

/* @var $subscription app\models\Subscription  */

$group = $subscription->group;
$notes = $subscription->notes;
?>

<h2><?= \Yii::t('app/mail', 'New Notes') ?></h2>
<p>
    <?= \Yii::t('app/mail', 'New notes were added') ?><br>
    <?= \Yii::t('app/mail', 'Course') ?>: <?= Html::encode($group->course->name) ?>
    <?php if (!empty($group->number) && !$group->isExamGroup) : ?>
        (<?= \Yii::t('app/mail', 'group') ?>: <?= $group->number ?>)
    <?php endif; ?>
    <br>
    <?= \Yii::t('app/mail', 'Notes') ?>: <?= Html::encode($notes) ?><br>
</p>
