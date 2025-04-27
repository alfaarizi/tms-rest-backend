<?php

use yii\helpers\Html;

/* @var $this \yii\web\View view component instance */
/* @var $message \yii\mail\BaseMessage instance of newly created mail message */

/* @var $task \app\models\Task The student solution tested */
/* @var $errorMsg string The structural requirement error message */

$group = $task->group;

?>

<h2><?= \Yii::t('app/mail', 'Failed structural requirements on submission') ?></h2>
<p>
    <?= \Yii::t('app/mail', 'Course') ?>: <?= Html::encode($group->course->name) ?>
    <?php if (!empty($group->number) && !$group->isExamGroup) : ?>
        (<?= \Yii::t('app/mail', 'group') ?>: <?= $group->number ?>)
    <?php endif; ?>
    <br>
    <?= \Yii::t('app/mail', 'Task name') ?>:
    <?= Html::a(Html::encode($task->name), Yii::$app->params['frontendUrl'] . '/student/task-manager/tasks/' . $task->id) ?><br>
<pre><?= Html::encode($errorMsg) ?></pre>
</p>
