<?php

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

?>

<?php
$tableData = [
    Html::encode($task->name),
    Yii::t('app/mail', $task->category)
];

$tableHeaders = [
    Yii::t('app/mail', 'Task name'),
    Yii::t('app/mail', 'Category')
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

<h2><?= Yii::t('app/mail', 'New task') ?></h2>
<?php if (!empty($task->group->number) && !$task->group->isExamGroup) : ?>
    <?=
    MailHtml::p(
        Yii::t('app/mail', 'New task was assigned to the course {course} (group: {group}).', [
            'course' => Html::encode($task->group->course->name),
            'group' => $task->group->number
        ])
    );
    ?>
<?php else : ?>
    <?=
    MailHtml::p(
        Yii::t('app/mail', 'New task was assigned to the course {course}.', [
            'course' => Html::encode($task->group->course->name)
        ])
    );
    ?>
<?php endif; ?>
<?php if (!$task->group->isExamGroup) : ?>
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
    $tableHeaders
);
?>
<?php if (!$task->entryPasswordProtected) : ?>
    <?= $this->render('../partials/taskDescription', ['task' => $task]) ?>
<?php endif; ?>
