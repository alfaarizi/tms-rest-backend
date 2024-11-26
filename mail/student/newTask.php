<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\DateTimeHelpers;

/* @var $this \yii\web\View view component instance */
/* @var $message \yii\mail\BaseMessage instance of newly created mail message */

/* @var $actor \app\models\User The actor of the action */
/* @var $task \app\models\Task The new task added */

?>

<h2><?= \Yii::t('app/mail', 'New task') ?></h2>
<p>
    <?php if (!empty($task->group->number) && !$task->group->isExamGroup) : ?>
        <?= \Yii::t('app/mail', 'New task was assigned to the course {course} (group: {group}).', [
        'course' => Html::encode($task->group->course->name),
        'group' => $task->group->number
    ]) ?>
    <?php else : ?>
        <?= \Yii::t('app/mail', 'New task was assigned to the course {course}.', [
        'course' => Html::encode($task->group->course->name)
    ]) ?>
    <?php endif; ?>
    <br>
    <?php if (!$task->group->isExamGroup) : ?>
        <?= \Yii::t('app/mail', 'Modifier') ?>: <?= Html::encode($actor->name) ?>
    <?php endif; ?>
</p>
<p>
    <?= \Yii::t('app/mail', 'Task name') ?>: <?= Html::encode($task->name) ?><br>
    <?= \Yii::t('app/mail', 'Category') ?>: <?=\Yii::t('app/mail', $task->category)?><br>
<?php if (!empty($task->available)) : ?>
    <?= \Yii::t('app/mail', 'Available from') ?>: <?= DateTimeHelpers::timeZoneConvert($task->available, $task->group->timezone, true) ?><br>
<?php endif; ?>
<?php if (!empty($task->softDeadline)) : ?>
    <?= \Yii::t('app/mail', 'Soft deadline of task') ?>: <?= DateTimeHelpers::timeZoneConvert($task->softDeadline, $task->group->timezone, true) ?><br>
<?php endif; ?>
    <?= \Yii::t('app/mail', 'Hard deadline of task') ?>: <?= DateTimeHelpers::timeZoneConvert($task->hardDeadline, $task->group->timezone, true) ?><br>
</p>

<?php if (!$task->entryPasswordProtected) : ?>
    <?= $this->render('../partials/taskDescription', ['task' => $task]) ?>
<?php endif; ?>
