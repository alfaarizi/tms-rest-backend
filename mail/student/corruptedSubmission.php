<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\DateTimeHelpers;

/* @var $this \yii\web\View view component instance */
/* @var $message \yii\mail\BaseMessage instance of newly created mail message */

/* @var $submission \app\models\Submission The student solution tested */

$task = $submission->task;
$group = $task->group;

?>

<h2><?= \Yii::t('app/mail', 'Corrupted Submission') ?></h2>
<p>
    <?= \Yii::t('app/mail', 'Your submitted file to Canvas is corrupted.') ?><br>
    <?= \Yii::t('app/mail', 'Course') ?>: <?= Html::encode($group->course->name) ?>
    <?php if (!empty($group->number) && !$group->isExamGroup) : ?>
        (<?= \Yii::t('app/mail', 'group') ?>: <?= $group->number ?>)
    <?php endif; ?>
    <br>
    <?= \Yii::t('app/mail', 'Task name') ?>:
    <?= Html::a(Html::encode($task->name), Yii::$app->params['frontendUrl'] . '/student/task-manager/tasks/' . $task->id) ?><br>
<pre><?= Html::encode($submission->safeErrorMsg) ?></pre>
</p>
