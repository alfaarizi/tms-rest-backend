<?php

use app\mail\layouts\MailHtml;
use yii\helpers\Html;
use yii\mail\BaseMessage;
use yii\web\View;
use app\components\DateTimeHelpers;

/* @var $this View view component instance */
/* @var $message BaseMessage instance of newly created mail message */

/* @var $data array tasks and optional student files with near deadlines */
/* @var $daysToDeadline integer The digest interval */
?>

<h2><?= Yii::t('app/mail', 'Oncoming submission deadlines') ?></h2>
<?=
MailHtml::p(
    Yii::t(
        'app/mail',
        'You have due submissions with deadline in the next {days} days:',
        ['days' => $daysToDeadline]
    )
);
?>
<?php
foreach ($data as $datum) {
    echo MailHtml::table([
        [
            'th' => Yii::t('app/mail', 'Course'),
            'td' => Html::encode($datum['task']->group->course->name)
        ],
        [
            'th' => Yii::t('app/mail', 'Task name'),
            'td' => Html::a(
                Html::encode($datum['task']->name),
                Yii::$app->params['frontendUrl'] . '/student/task-manager/tasks/' . $datum['task']->id
            )
        ],
        [
            'th' => Yii::t('app/mail', 'Hard deadline of task'),
            'td' =>  DateTimeHelpers::timeZoneConvert(
                $datum['task']->hardDeadline,
                $datum['task']->group->timezone,
                true
            )
        ],
        [
            'th' => Yii::t('app/mail', 'Solution last submitted at'),
            'td' => ($datum['submission'] != null) ? DateTimeHelpers::timeZoneConvert(
                $datum['submission']->uploadTime,
                $datum['task']->group->timezone,
                true
            ) : ''
        ],
        [
            'th' => Yii::t('app/mail', 'Status of latest submission'),
            'td' => ($datum['submission'] != null) ? ($datum['submission']->translatedStatus) : ''
        ]
    ]);
}
?>
<?=
MailHtml::p(
    Yii::t('app/mail', 'The table does not contain submissions you have already successfully completed.')
)
?>
