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
<?=
MailHtml::table([
    [
        'th' => Yii::t('app/mail', 'Modifier'),
        'td' => Html::encode($actor->name),
        'cond' => (!$task->group->isExamGroup)
    ]
])
?>
<?=
MailHtml::table([
    [
        'th' => Yii::t('app/mail', 'Task name'),
        'td' => Html::encode($task->name)
    ],
    [
        'th' => Yii::t('app/mail', 'Category'),
        'td' => Yii::t('app/mail', $task->category)
    ],
    [
        'th' => Yii::t('app/mail', 'Available from'),
        'td' => DateTimeHelpers::timeZoneConvert($task->available, $task->group->timezone, true),
        'cond' => (!empty($task->available))
    ],
    [
        'th' => Yii::t('app/mail', 'Soft deadline of task'),
        'td' => DateTimeHelpers::timeZoneConvert($task->softDeadline, $task->group->timezone, true),
        'cond' => (!empty($task->softDeadline))
    ],
    [
        'th' => Yii::t('app/mail', 'Hard deadline of task'),
        'td' => DateTimeHelpers::timeZoneConvert($task->hardDeadline, $task->group->timezone, true),
        'cond' => (!empty($task->hardDeadline))
    ],
])
?>
<?php if (!$task->entryPasswordProtected) : ?>
    <?= $this->render('../partials/taskDescription', ['task' => $task]) ?>
<?php endif; ?>
