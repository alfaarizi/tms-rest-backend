<?php

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
<p>
    <b><?=
        Yii::t(
            'app/mail',
            'You have due submissions with deadline in the next {days} days:',
            ['days' => $daysToDeadline]
        )
        ?></b>
</p>
<table>
    <tr>
        <th><?= Yii::t('app/mail', 'Course') ?></th>
        <th><?= Yii::t('app/mail', 'Task name') ?></th>
        <th><?= Yii::t('app/mail', 'Hard deadline of task') ?></th>
        <th><?= Yii::t('app/mail', 'Solution last submitted at') ?></th>
        <th><?= Yii::t('app/mail', 'Status of latest submission') ?></th>
    </tr>
    <?php foreach ($data as $datum) : ?>
        <tr>
            <td><?= Html::encode($datum['task']->group->course->name) ?></td>
            <td><?=
                Html::a(
                    Html::encode($datum['task']->name),
                    Yii::$app->params['frontendUrl'] . '/student/task-manager/tasks/' . $datum['task']->id
                )
                ?>
            </td>
            <td><?=
                DateTimeHelpers::timeZoneConvert(
                    $datum['task']->hardDeadline,
                    $datum['task']->group->timezone,
                    true
                )
                ?></td>
            <?php if ($datum['submission'] != null) : ?>
                <td><?=
                    DateTimeHelpers::timeZoneConvert(
                        $datum['submission']->uploadTime,
                        $datum['task']->group->timezone,
                        true
                    )
                    ?></td>
                <td><?= $datum['submission']->translatedStatus ?></td>
            <?php else : ?>
                <td></td>
                <td></td>
            <?php endif ?>
        </tr>
    <?php endforeach; ?>
</table>
<p>
    <?= Yii::t('app/mail', 'The table does not contain submissions you have already successfully completed.') ?>
</p>
