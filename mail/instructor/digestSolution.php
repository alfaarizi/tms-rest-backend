<?php

use app\models\StudentFile;
use yii\helpers\Html;
use yii\mail\BaseMessage;
use yii\web\View;

/* @var $this View view component instance */
/* @var $message BaseMessage instance of newly created mail message */

/* @var $solutions StudentFile[] The new student solution submitted */
/* @var $hours integer The digest interval */
?>

<h2><?= Yii::t('app/mail', 'Submitted solutions') ?></h2>
<p>
    <b><?= Yii::t('app/mail', 'Student solutions submitted in the past {hours} hours', ['hours' => $hours]) ?>:</b>
</p>
<ul>
<?php foreach ($solutions as $solution) : ?>
    <li>
        <?= Yii::t('app/mail', 'Name') ?>: <?= Html::encode($solution->uploader->name) ?> (<?= Html::encode($solution->uploader->userCode) ?>)<br>
        <?= Yii::t('app/mail', 'Course') ?>: <?= Html::encode($solution->task->group->course->name) ?>

        <?php if (!empty($solution->task->group->number)) : ?>
            (<?= Yii::t('app/mail', 'group') ?>: <?= $solution->task->group->number ?>)
        <?php endif; ?><br>

        <?= Yii::t('app/mail', 'Task name')?>: <?= Html::encode($solution->task->name) ?><br>

        <?php if ($solution->isAccepted == StudentFile::IS_ACCEPTED_CORRUPTED) : ?>
            <div style="color: #dc4126;"> <?= Yii::t('app/mail', 'Corrupted') ?> </div> <br>
        <?php endif; ?>

        <?= Html::a(
            Yii::t('app/mail', 'View solution'),
            Yii::$app->params['frontendUrl'] . '/instructor/task-manager/student-files/' . $solution->id
        )
        ?>
    </li>
<?php endforeach; ?>
</ul>
<p>
    <?= Yii::t('app/mail', 'The list does not contain the already graded solutions.') ?>
</p>
