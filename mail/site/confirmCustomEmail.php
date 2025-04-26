<?php

use app\mail\layouts\MailHtml;
use yii\helpers\Html;
use yii\mail\BaseMessage;
use yii\web\View;

/* @var $this View view component instance */
/* @var $message BaseMessage instance of newly created mail message */
/* @var $user \app\models\User The user to send the confirmation email to */
/* @var $url string The confirmation URL */

?>

<h2><?= Yii::t('app/mail', 'Please confirm your custom email address') ?></h2>
<?=
MailHtml::p(
    Yii::t(
        'app/mail',
        'Someone, probably you, added this email address to the account of {name} in the Task Management System.',
        ['name' => Html::encode($user->name)]
    )
)
?>
<?=
MailHtml::p(
    Yii::t(
        'app/mail',
        'If it was you, please click the following link to confirm the address: {link}',
        ['link' => Html::a($url, $url)]
    )
)
?>
<?=
MailHtml::p(
    Yii::t('app/mail', 'If it wasn’t you, you may disregard this email.')
)
?>
