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
MailHtml::table([
    [
        'th' => "Kurzusnév minta",
        'td' => Html::encode($courseArg)
    ],
    [
        'th' => "Feladat sorszám",
        'td' => Html::encode($taskArg)
    ],
    [
        'th' => "Szemeszter",
        'td' => Html::encode($semester->name)
    ]
])
?>
<?=
MailHtml::p(
    "Lekérdezés eredménye"
)
?>
<?php foreach ($results as $result) : ?>
    <?=
    MailHtml::table([
        ['th' => "Név", 'td' => Html::encode("{$result['userName']} ({$result['userCode']})")],
        ['th' => "Kurzus", 'td' => Html::encode("{$result['courseName']} ({$result['courseCode']}/{$result['groupNumber']})")],
        ['th' => "Feladat", 'td' => $result['taskName']],
        ['th' => "Állapot", 'td' =>  (!empty($result['status'])) ? Yii::t('app', $result['status'], [], 'hu') : "Nem beküldött"]
    ])
    ?>
<?php endforeach; ?>
