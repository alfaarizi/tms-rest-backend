<?php

use app\models\Submission;
use app\mail\layouts\MailHtml;
use yii\helpers\Html;
use yii\mail\BaseMessage;
use yii\web\View;

/* @var $this View view component instance */
/* @var $message BaseMessage instance of newly created mail message */
/* @var $actor \app\models\User The actor of the action */
/* @var $submission Submission The student solution graded */

$group = $submission->task->group;
?>

<h2><?= Yii::t('app/mail', 'Graded submission') ?></h2>
<?=
MailHtml::p(
    Yii::t('app/mail', 'New grade for previous submission has been recorded.')
)
?>
<?=
MailHtml::table([
    [
        'th' => Yii::t('app/mail', 'Course'),
        'td' => Html::encode($group->course->name)
    ],
    [
        'th' => Yii::t('app/mail', 'group'),
        'td' => $group->number,
        'cond' => (!empty($group->number) && !$group->isExamGroup)
    ],
    [
        'th' => Yii::t('app/mail', 'Status'),
        'td' => Yii::t('app', $submission->status)
    ],
    [
        'th' => Yii::t('app/mail', 'Grade'),
        'td' => $submission->grade
    ],
    [
        'th' => Yii::t('app/mail', 'Remark'),
        'td' => Html::encode(nl2br($submission->notes, false))
    ],
    [
        'th' => Yii::t('app/mail', 'Modifier'),
        'td' => Html::encode($actor->name),
        'cond' => (!$group->isExamGroup)
    ]
])
?>
