<?php

use yii\helpers\Html;
use yii\mail\MessageInterface;
use yii\web\View;

/* @var $this View view component instance */
/* @var $message MessageInterface the message being composed */
/* @var $content string main view render result */

?>

<?php $this->beginPage() ?>
<?php

$logoPath = Yii::getAlias('@app/web/logo192-inverted.png');
$logoBase64 = (is_file($logoPath) && ($logoImg = file_get_contents($logoPath)))
    ? 'data:image/png;base64,' . base64_encode($logoImg)
    : '';

$headerText = 'Notice';
if (preg_match('/<h2>(.*?)<\/h2>/is', $content, $matches)) {
    $headerText = trim($matches[1]);
    $content = str_replace($matches[0], '', $content);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="">

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=<?= Yii::$app->charset ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="format-detection" content="telephone=no, date=no, address=no, email=no, url=no">
        <meta name="x-apple-disable-message-reformatting" content="">
        <meta name="color-scheme" content="light dark">
        <meta name="supported-color-schemes" content="light dark">
        <title><?= Html::encode($this->title) ?></title>
        <!--[if mso]>
        <style>
            table {border-collapse: collapse;}
            body, table, td {font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif !important;}
        </style>
        <![endif]-->
        <?php $this->head() ?>
    </head>

    <body
        style="
        margin: 0;
        padding: 0;
        width: 100%;
        -webkit-font-smoothing: antialiased;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        background-color: #fafafa;"
    >

        <!-- Branding Line start -->
        <div style="width: 100%; height: 4px; background-color: #231F20;"></div>
        <!-- Branding Line end -->

        <?php $this->beginBody() ?>
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
            <tbody>

            <!-- Branding Name start -->
            <tr>
                <td style="background-color: #fafafa; padding: 24px 0; text-align: center;">
                    <h1
                        style="
                        margin: 0;
                        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                        font-size: 24px;
                        color: #231F20;"
                    >
                        <?= Yii::t('app/mail', 'Task Management System') ?>
                    </h1>
                </td>
            </tr>
            <!-- Branding Name end -->

            <!-- Container start-->
            <tr>
                <td align="center">
                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0" style="max-width: 540px; margin: 0 auto;">
                        <tbody>

                        <!-- Content Section start -->
                        <tr>
                            <td style="border-radius:3px; border:1px solid #ededed; padding: 18px 25px; background-color: #ffffff;">
                                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                    <tbody>
                                    <tr>
                                        <td>
                                            <!-- Header Box start -->
                                            <div
                                                style="
                                                background-color: #231F20;
                                                padding: 8px;
                                                text-align: center;
                                                border-radius:3px;
                                                border:1px solid #ededed;
                                                margin-bottom: 8px;"
                                            >
                                                <p
                                                    style="
                                                    margin: 0;
                                                    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                                                    font-size: 14px;
                                                    color: #ffffff;"
                                                >
                                                    <?= $headerText ?>
                                                </p>
                                            </div>
                                            <!-- Header Box end -->

                                            <!-- Content Box start -->
                                            <div
                                                style="
                                                margin: 0;
                                                background-color: #ffffff;"
                                            >
                                                <?= $content ?>
                                            </div>
                                            <!-- Content Box end -->
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <!-- Content Section end -->

                        <!-- Spacer start-->
                        <tr>
                            <td style="height: 8px"></td>
                        </tr>
                        <!-- Spacer end-->

                        <!-- Branding Section start -->
                        <tr>
                            <td>
                                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                    <tbody>
                                    <tr>
                                        <td width="50%" style="text-align: right; padding-right: 2px;">
                                            <img
                                                src="<?= $logoBase64 ?>"
                                                alt=""
                                                width="30"
                                                height="30"
                                                style="
                                                vertical-align: middle;
                                                display: inline-block;"
                                            >
                                        </td>
                                        <td width="50%" style="text-align: left; padding-left: 2px;">
                                            <span
                                                style="
                                                margin: 0;
                                                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                                                font-weight: 700;
                                                font-size: 14px;
                                                color: #231F20;
                                                vertical-align: middle;"
                                            >
                                                TMS
                                            </span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <!-- Branding Section end -->

                        <!-- Footer Section start -->
                        <tr>
                            <td style="padding: 8px 16px 16px; background-color: #fafafa;">
                                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                                    <tbody>
                                    <tr>
                                        <td style="text-align: center; padding-bottom: 8px;">
                                            <p
                                                style="
                                                margin: 0;
                                                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                                                font-size: 13px;
                                                color: #5c5c5c;"
                                            >
                                                <?= Yii::t('app/mail', 'You are receiving this email because of your account on') ?>
                                                <?= Yii::t('app/mail', 'Task Management System') ?>.
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center;">
                                            <a
                                                href="<?= Yii::$app->params['frontendUrl'] ?>"
                                                target="_blank"
                                                style="
                                                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                                                font-size: 13px;
                                                color: #1068bf;
                                                text-decoration: underline;"
                                            >
                                                <?= Yii::t('app/mail', 'View in the browser') ?>
                                            </a>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <!-- Footer Section end -->

                        </tbody>
                    </table>
                </td>
            </tr>
            <!-- Container end -->

            </tbody>
        </table>
        <?php $this->endBody() ?>
    </body>

</html>
<?php $this->endPage() ?>
