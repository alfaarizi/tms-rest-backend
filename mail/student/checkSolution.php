<?php

use yii\helpers\Html;

/* @var $this \yii\web\View view component instance */
/* @var $message \yii\mail\BaseMessage instance of newly created mail message */

/* @var $submission \app\models\Submission The student solution tested */

$task = $submission->task;
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
    <?= \Yii::t('app/mail', 'Task name') ?>:
    <?= Html::a(Html::encode($task->name), Yii::$app->params['frontendUrl'] . '/student/task-manager/tasks/' . $task->id) ?><br>
    <?= \Yii::t('app/mail', 'Result') ?>: <?= \Yii::t('app', $submission->status) ?><br>
    <?= \Yii::t('app/mail', 'Remark') ?>:<br>
    <pre><?= Html::encode($submission->safeErrorMsg) ?></pre>
</p>
