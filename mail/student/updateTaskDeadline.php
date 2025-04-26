<?php

use app\models\Group;
use app\models\Task;
use app\mail\layouts\MailHtml;
use yii\helpers\Html;
use app\components\DateTimeHelpers;
use yii\mail\BaseMessage;
use yii\web\View;

/* @var $this View view component instance */
/* @var $message BaseMessage instance of newly created mail message */
/* @var $actor \app\models\User The actor of the action */
/* @var $task Task The new task added */
/* @var $group Group The group */

?>

<?php
$tableData = [
    Html::encode($task->name),
    Yii::t('app/mail', $task->category)
];

$tableHeaders = [
    Yii::t('app/mail', 'Task name'),
    Yii::t('app/mail', 'Category'),
];

if (!empty($task->available)) {
    $tableData[] = DateTimeHelpers::timeZoneConvert($task->available, $task->group->timezone, true);
    $tableHeaders[] = Yii::t('app/mail', 'Available from');
}

if (!empty($task->softDeadline)) {
    $tableData[] = DateTimeHelpers::timeZoneConvert($task->softDeadline, $task->group->timezone, true);
    $tableHeaders[] = Yii::t('app/mail', 'Soft deadline of task');
}

if (!empty($task->hardDeadline)) {
    $tableData[] = DateTimeHelpers::timeZoneConvert($task->hardDeadline, $task->group->timezone, true);
    $tableHeaders[] = Yii::t('app/mail', 'Hard deadline of task');
}
?>

<h2><?= Yii::t('app/mail', 'Task deadline change') ?></h2>
<?php if (!empty($task->group->number) && !$group->isExamGroup) : ?>
    <?=
    MailHtml::p(
        Yii::t('app/mail', 'The deadline of task {task} for the course {course} (group: {group}) was modified.', [
            'course' => Html::encode($task->group->course->name),
            'group' => $task->group->number,
            'task' => Html::encode($task->name)
        ])
    );
    ?>
<?php else : ?>
    <?=
    MailHtml::p(
        Yii::t('app/mail', 'The deadline of task {task} for the course {course} was modified.', [
            'course' => Html::encode($task->group->course->name),
            'task' => Html::encode($task->name)
        ])
    )
    ?>
<?php endif; ?>
<?php if (!$group->isExamGroup) : ?>
    <?=
    MailHtml::table(
        [Html::encode($actor->name)],
        [Yii::t('app/mail', 'Modifier')]
    )
    ?>
<?php endif; ?>
<?=
MailHtml::table(
    $tableData,
    $tableHeaders,
)
?>
<?php if (!$task->entryPasswordProtected) : ?>
    <?= $this->render('../partials/taskDescription', ['task' => $task]) ?>
<?php endif; ?>
