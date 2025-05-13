<?php

use app\models\CodeCompassInstance;
use app\mail\layouts\MailHtml;
use yii\helpers\Html;
use yii\mail\BaseMessage;
use yii\web\View;

/* @var $this View view component instance */
/* @var $message BaseMessage instance of newly created mail message */
/* @var $instance CodeCompassInstance The CodeCompass instance that was started */

function getCodeCompassUrl($port): string
{
    $url = parse_url(Yii::$app->params['backendUrl']);
    return $url["scheme"] . '://' . $url["host"] . ':' . $port;
}

?>

<h2><?= Yii::t('app/mail', 'CodeCompass started') ?></h2>
<?=
MailHtml::p(
    Yii::t('app/mail', 'Your requested CodeCompass instance has now been started for the solution of {name}, course: {course}.', [
        'name' => Html::encode($instance->submissons->uploader->name) ,
        'course' => Html::encode($instance->submissons->task->group->course->name)
    ])
);
?>
<?=
MailHtml::p(
    Yii::t('app/mail', 'Link to your CodeCompass instance: ') . Html::a("CodeCompass", getCodeCompassUrl($instance->port)),
    ['textAlign' => 'left']
)
?>
<?=
MailHtml::p(
    Yii::t('app/mail', 'Your username: {username}', [
        'username' => Html::encode($instance->username)
    ]),
    ['textAlign' => 'left']
)
?>
<?=
MailHtml::p(
    Yii::t('app/mail', 'Your password: {password}', [
        'password' => Html::encode($instance->password)
    ]),
    ['textAlign' => 'left']
)
?>
