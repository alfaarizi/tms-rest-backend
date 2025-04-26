<?php

use app\models\Task;
use app\mail\layouts\MailHtml;
use yii\helpers\Markdown;
use yii\helpers\HtmlPurifier;
use yii\web\View;

/* @var $this View view component instance */
/* @var $task Task The new group subscribed */
?>

<?php if (empty($task->available)) : ?>
    <?=
    MailHtml::p(
        Yii::t('app/mail', 'Task description')
    )
    ?>
    <?=
    MailHtml::p(
        $task->category == 'Canvas tasks' ?
             nl2br($task->description)
             : HtmlPurifier::process(Markdown::process($task->description, 'gfm'))
    )
    ?>
<?php endif;
