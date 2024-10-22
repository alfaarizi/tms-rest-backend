<?php

use yii\helpers\Html;

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
    <li>Kurzusnév minta: %<?= Html::encode($courseArg) ?>%</li>
    <li>Feladat sorszám: <?= Html::encode($taskArg) ?></li>
    <li>Szemeszter: <?= Html::encode($semester->name) ?></li>
</ul>

<b>Lekérdezés eredménye:</b>
<ul>
<?php foreach ($results as $result) : ?>
    <li>
        Név: <?= Html::encode($result['userName']) ?> (<?= Html::encode($result['userCode']) ?>)<br>
        Kurzus: <?= Html::encode($result['courseName']) ?> (<?= $result['courseCode'] ?>/<?= $result['groupNumber'] ?>)<br>
        Feladat: <?= Html::encode($result['taskName']) ?><br>
        Állapot: <?= !empty($result['isAccepted']) ? Yii::t('app', $result['isAccepted'], [], 'hu') : 'Nem beküldött' ?>
    </li>
<?php endforeach; ?>
</ul>
