<?php

use app\models\CodeCompassInstance;
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
<p>
    <?= Yii::t('app/mail', 'Your requested CodeCompass instance has now been started for the solution of {name}, course: {course}.', [
        'name' => $instance->studentFile->uploader->name,
        'course' => $instance->studentFile->task->group->course->name
    ]) ?>
</p>
<p>
    <?= Yii::t('app/mail', 'Link to your CodeCompass instance: ') ?>
    <a href="<?= getCodeCompassUrl($instance->port) ?>">CodeCompass</a>
</p>
<p>
    <?= Yii::t('app/mail', 'Your username: {username}', [
        'username' => $instance->username
    ]) ?>
    <br>
    <?= Yii::t('app/mail', 'Your password: {password}', [
        'password' => $instance->password
    ]) ?>
</p>
