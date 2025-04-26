<?php

use app\models\Semester;
use app\mail\layouts\MailHtml;
use yii\helpers\Html;
use yii\mail\BaseMessage;
use yii\web\View;

/* @var $this View view component instance */
/* @var $message BaseMessage instance of newly created mail message */
/* @var $courseArg string The course name pattern argument */
/* @var $taskArg int The task serial argument */
/* @var $semester Semester The queried semester */
/* @var $results array The digest interval */
?>

<h2>Nem elfogadott megoldások</h2>
<?=
MailHtml::p(
    "Lekérdezés paraméterei"
)
?>
<?=
MailHtml::table(
    [Html::encode($courseArg), Html::encode($taskArg), Html::encode($semester->name)],
    ["Kurzusnév minta", "Feladat sorszám", "Szemeszter"]
)
?>
<?=
MailHtml::p(
    "Lekérdezés eredménye"
)
?>
<?php foreach ($results as $result) : ?>
    <?php
    $tableData = [
    Html::encode($result['userName']) . ' ' . (Html::encode($result['userCode'])),
    Html::encode($result['courseName']) . ' (' . $result['courseCode'] . '/' . $result['groupNumber'] . ')',
    Html::encode($result['taskName']),
    ];

    $tableHeaders = [
    "Név", "Kurzus", "Feladat"
    ];

    if (!empty($result['status'])) {
        $tableData[] = Yii::t('app', $result['status'], [], 'hu');
    } else {
        $tableData[] = "Nem beküldött";
    }
    $tableHeaders[] = "Állapot";
    ?>
    <?=
    MailHtml::table(
        $tableData,
        $tableHeaders
    )
    ?>
<?php endforeach; ?>
