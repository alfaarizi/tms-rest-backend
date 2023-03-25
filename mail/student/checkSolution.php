<?php

use yii\helpers\Html;

/* @var $this \yii\web\View view component instance */
/* @var $message \yii\mail\BaseMessage instance of newly created mail message */

/* @var $studentFile \app\models\StudentFile The student solution tested */

$task = $studentFile->task;
$group = $task->group;
?>

<h2><?= \Yii::t('app/mail', 'Automated submission test ready') ?></h2>
<p>
    <?= \Yii::t('app/mail', 'Automated testing on your previously submitted solution is ready.') ?><br>
    <?= \Yii::t('app/mail', 'Course') ?>: <?= Html::encode($group->course->name) ?>
    <?php if (!empty($group->number) && !$group->isExamGroup) : ?>
        (<?= \Yii::t('app/mail', 'group') ?>: <?= $group->number ?>)
    <?php endif; ?>
    <br>
    <?= \Yii::t('app/mail', 'Task name') ?>: <?= Html::encode($task->name) ?><br>
    <?= \Yii::t('app/mail', 'Result') ?>: <?= \Yii::t('app', $studentFile->isAccepted) ?><br>
    <?= \Yii::t('app/mail', 'Remark') ?>:<br>
    <pre><?= Html::encode($studentFile->safeErrorMsg) ?></pre>
</p>
