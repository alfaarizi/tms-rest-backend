<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View view component instance */
/* @var $message \yii\mail\BaseMessage instance of newly created mail message */

/* @var $actor \app\models\User The actor of the action */
/* @var $group \app\models\Group The new group subscribed */

?>

<h2><?= \Yii::t('app/mail', 'New course assignment') ?></h2>
<p>
    <?php if (!empty($course->name)) : ?>
        <?= \Yii::t('app/mail', 'You have been assigned to the course {course} as a lecturer.', [
        'course' => $course->name,
    ]) ?>
    <?php else : ?>
        <?= \Yii::t('app/mail', 'You have been assigned to a course.') ?>
    <?php endif; ?>
    <br>
    <?= \Yii::t('app/mail', 'Modifier') ?>: <?= Html::encode($actor->name) ?>
</p>
