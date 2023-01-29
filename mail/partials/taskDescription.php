<?php

use yii\helpers\Markdown;
use yii\helpers\HtmlPurifier;

/* @var $this \yii\web\View view component instance */
/* @var $task \app\models\Task The new group subscribed */
?>

<?php if (empty($task->available)) : ?>
    <p><?= Yii::t('app/mail', 'Task description') ?>: </p>
    <div>
        <?= $task->category == 'Canvas tasks' ?
            nl2br($task->description)
            : HtmlPurifier::process(Markdown::process($task->description, 'gfm'))
        ?>
    </div>
<?php endif; ?>
