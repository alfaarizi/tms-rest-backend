<?php

use app\models\Group;
use app\mail\layouts\MailHtml;
use yii\helpers\Html;
use yii\mail\BaseMessage;
use yii\web\View;

/* @var $this View view component instance */
/* @var $message BaseMessage instance of newly created mail message */
/* @var $actor \app\models\User The actor of the action */
/* @var $group Group The new group subscribed */

?>

<h2><?= Yii::t('app/mail', 'Added to new group') ?></h2>
<?php if (!empty($group->number)) : ?>
    <?=
    MailHtml::p(
        Yii::t('app/mail', 'You have been assigned to the course {course} (group: {group}).', [
            'course' => Html::encode($group->course->name),
            'group' => $group->number
        ])
    );
    ?>
<?php else : ?>
    <?=
    MailHtml::p(
        Yii::t('app/mail', 'You have been assigned to the course {course}.', [
            'course' => Html::encode($group->course->name)
        ])
    );
    ?>
<?php endif; ?>
<?=
MailHtml::table(
    [Html::encode($actor->name)],
    [Yii::t('app/mail', 'Modifier')]
);
?>
