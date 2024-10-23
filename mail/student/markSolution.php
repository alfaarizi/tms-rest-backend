<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View view component instance */
/* @var $message \yii\mail\BaseMessage instance of newly created mail message */

/* @var $actor \app\models\User The actor of the action */
/* @var $submission \app\models\Submission The student solution graded */

$group = $submission->task->group;
?>

<h2><?= \Yii::t('app/mail', 'Graded submission') ?></h2>
<p>
    <?= \Yii::t('app/mail', 'New grade for previous submission has been recorded.') ?><br>
    <?= \Yii::t('app/mail', 'Course') ?>: <?= Html::encode($group->course->name) ?>
    <?php if (!empty($group->number) && !$group->isExamGroup) : ?>
        (<?= \Yii::t('app/mail', 'group') ?>: <?= $group->number ?>)
    <?php endif; ?>
    <br>
    <?= \Yii::t('app/mail', 'Status') ?>: <?= \Yii::t('app', $submission->status) ?><br>
    <?= \Yii::t('app/mail', 'Grade') ?>: <?= $submission->grade ?><br>
    <?= \Yii::t('app/mail', 'Remark') ?>: <?= Html::encode(nl2br($submission->notes, false)) ?><br>
    <?php if (!$group->isExamGroup) : ?>
        <?= \Yii::t('app/mail', 'Modifier') ?>: <?= Html::encode($actor->name) ?>
    <?php endif; ?>
</p>
