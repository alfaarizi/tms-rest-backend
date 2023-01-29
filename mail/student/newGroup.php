<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View view component instance */
/* @var $message \yii\mail\BaseMessage instance of newly created mail message */

/* @var $actor \app\models\User The actor of the action */
/* @var $group \app\models\Group The new group subscribed */

?>

<h2><?= \Yii::t('app/mail', 'New group assignment') ?></h2>
<p>
    <?php if (!empty($group->number) && !$group->isExamGroup) : ?>
        <?= \Yii::t('app/mail', 'You have been assigned to the course {course} (group: {group}).', [
        'course' => Html::encode($group->course->name),
        'group' => $group->number
    ]) ?>
    <?php else : ?>
        <?= \Yii::t('app/mail', 'You have been assigned to the course {course}.', [
        'course' => Html::encode($group->course->name)
    ]) ?>
    <?php endif; ?>
    <br>
    <?php if (!$group->isExamGroup) : ?>
        <?= \Yii::t('app/mail', 'Modifier') ?>: <?= Html::encode($actor->name) ?>
    <?php endif; ?>
</p>
