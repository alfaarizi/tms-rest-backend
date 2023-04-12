<?php

return [
    'class' => \yii\symfonymailer\Mailer::class,
    'viewPath' => '@app/mail',
    // Comment this to enable real mail transport
    'useFileTransport' => true,
    // By default, NullTransport is utilized (sends no mail).
    // Uncomment and configure this for SendmailTransport.
    //'transport' => [
    //    'dsn' => 'sendmail://default',
    //],
    // Uncomment and configure this for SMTP mail transport
    //'transport' => [
    //    'dsn' => 'smtp://user:pass@smtp.example.com:25',
    //],
];
