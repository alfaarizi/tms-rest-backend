<?php

use app\mail\layouts\MailHtml;
use app\models\Task;
use yii\helpers\Html;
use yii\mail\BaseMessage;
use yii\web\View;

/* @var $this View view component instance */
/* @var $message BaseMessage instance of newly created mail message */

/* @var $task Task The student solution tested */
/* @var $errorMsg string The structural requirement error message */

$group = $task->group;

?>

<h2><?= Yii::t('app/mail', 'Failed structural requirements on submission') ?></h2>

<?=
MailHtml::table([
    [
        'th' => Yii::t('app/mail', 'Course'),
        'td' => Html::encode($group->course->name)
    ],
    [
        'th' => Yii::t('app/mail', 'group'),
        'td' =>  $group->number,
        'cond' => (!empty($group->number) && !$group->isExamGroup)
    ],
    [
        'th' => Yii::t('app/mail', 'task name'),
        'td' => Html::a(Html::encode($task->name), Yii::$app->params['frontendUrl'] . '/student/task-manager/tasks/' . $task->id)
    ]
])
?>
<?=
MailHtml::p(
    Html::encode($errorMsg)
)
?>
