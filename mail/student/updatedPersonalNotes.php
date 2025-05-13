<?php

use app\mail\layouts\MailHtml;
use yii\helpers\Html;
use yii\mail\BaseMessage;
use yii\web\View;

/* @var $this View view component instance */
/* @var $message BaseMessage instance of newly created mail message */
/* @var $subscription app\models\Subscription  */

$group = $subscription->group;
$notes = $subscription->notes;
?>

<h2><?= Yii::t('app/mail', 'New Notes') ?></h2>
<?=
MailHtml::p(
    Yii::t('app/mail', 'New notes were added')
)
?>
<?=
MailHtml::table([
    ['th' => Yii::t('app/mail', 'Course'), 'td' => Html::encode($group->course->name)],
    ['th' => Yii::t('app/mail', 'group'), 'td' => (!empty($group->number) && !$group->isExamGroup) ? $group->number : ''],
    ['th' => Yii::t('app/mail', 'Notes'), 'td' => Html::encode($notes)]
])
?>
