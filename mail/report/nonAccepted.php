<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View view component instance */
/* @var $message \yii\mail\BaseMessage instance of newly created mail message */

/* @var $courseArg string The course name pattern argument */
/* @var $taskArg int The task serial argument */
/* @var $semester \app\models\Semester The queried semester */
/* @var $results array The digest interval */
?>

<h2>Nem elfogadott megoldások</h2>

<b>Lekérdezés paraméterei:</b>
<ul>
    <li>Kurzusnév minta: %<?= $courseArg ?>%</li>
    <li>Feladat sorszám: <?= $taskArg ?></li>
    <li>Szemeszter: <?= $semester->name ?></li>
</ul>

<b>Lekérdezés eredménye:</b>
<ul>
<?php foreach ($results as $result) : ?>
    <li>
        Név: <?= $result['userName'] ?> (<?= $result['neptun'] ?>)<br>
        Kurzus: <?= $result['courseName'] ?> (<?= $result['courseCode'] ?>/<?= $result['groupNumber'] ?>)<br>
        Feladat: <?= $result['taskName'] ?><br>
        Állapot: <?= !empty($result['isAccepted']) ? Yii::t('app', $result['isAccepted'], [], 'hu') : 'Nem beküldött' ?>
    </li>
<?php endforeach; ?>
</ul>
