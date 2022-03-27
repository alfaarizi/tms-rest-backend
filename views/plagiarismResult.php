<?php

use yii\helpers\Url;

/* @var $id number */
/* @var $token string */
/* @var $number number */

$url = ['frame', 'id' => $id, 'token' => $token, 'number' => $number];
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
    "http://www.w3.org/TR/html4/frameset.dtd">
<html lang="en">
    <head>
        <title>Plagiarism result</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    </head>
    <frameset rows="150,*">
        <frameset cols="*">
            <frame src="<?= htmlspecialchars(Url::to($url + ['side' => 'top'])) ?>" name="top">
        </frameset>
        <frameset cols="50%,50%">
            <frame src="<?= htmlspecialchars(Url::to($url + ['side' => '0'])) ?>" name="0">
            <frame src="<?= htmlspecialchars(Url::to($url + ['side' => '1'])) ?>" name="1">
        </frameset>
    </frameset>
</html>
