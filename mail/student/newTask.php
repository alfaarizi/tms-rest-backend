<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View view component instance */
/* @var $message \yii\mail\BaseMessage instance of newly created mail message */

/* @var $actor \app\models\User The actor of the action */
/* @var $task \app\models\Task The new task added */

?>

<h2><?= \Yii::t('app/mail', 'New task') ?></h2>
<p>
    <?php if (!empty($task->group->number) && !$task->group->isExamGroup) : ?>
        <?= \Yii::t('app/mail', 'New task was assigned to the course {course} (group: {group}).', [
        'course' => $task->group->course->name,
        'group' => $task->group->number
    ]) ?>
    <?php else : ?>
        <?= \Yii::t('app/mail', 'New task was assigned to the course {course}.', [
        'course' => $task->group->course->name
    ]) ?>
    <?php endif; ?>
    <br>
    <?php if (!$task->group->isExamGroup) : ?>
        <?= \Yii::t('app/mail', 'Modifier') ?>: <?= $actor->name ?>
    <?php endif; ?>
</p>
<p>
    <?= \Yii::t('app/mail', 'Task name') ?>: <?= $task->name ?><br>
    <?= \Yii::t('app/mail', 'Category') ?>: <?=\Yii::t('app/mail', $task->category)?><br>
<?php if (!empty($task->available)) : ?>
    <?= \Yii::t('app/mail', 'Available from') ?>: <?= $task->available ?><br>
<?php endif; ?>
<?php if (!empty($task->softDeadline)) : ?>
    <?= \Yii::t('app/mail', 'Soft deadline of task') ?>: <?= $task->softDeadline ?><br>
<?php endif; ?>
    <?= \Yii::t('app/mail', 'Hard deadline of task') ?>: <?= $task->hardDeadline ?><br>
<?php if (empty($task->available)) : ?>
    <?= \Yii::t('app/mail', 'Task description') ?>:<br>
    <?= nl2br($task->description, false) ?>
<?php endif; ?>
</p>
