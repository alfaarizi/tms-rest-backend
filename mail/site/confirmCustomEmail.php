<?php

/* @var $this \yii\web\View view component instance */
/* @var $message \yii\mail\BaseMessage instance of newly created mail message */
/* @var $user \app\models\User The user to send the confirmation email to */
/* @var $url string The confirmation URL */

use yii\helpers\Html;

?>

<h2><?= \Yii::t('app/mail', 'Please confirm your custom email address') ?></h2>
<p><?= \Yii::t(
    'app/mail',
    'Someone, probably you, added this email address to the account of {name} in the Task Management System.',
    ['name' => $user->name]
) ?></p>
<p><?= \Yii::t(
    'app/mail',
    'If it was you, please click the following link to confirm the address: {link}',
    ['link' => Html::a($url, $url)]
) ?></p>
<p><?= \Yii::t('app/mail', 'If it wasn’t you, you may disregard this email.') ?></p>
