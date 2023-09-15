<?php

return [
    'db' => [
        'class' => \yii\db\Connection::class,
        // Schema cache options (for production environment)
        'enableSchemaCache' => YII_ENV_PROD,
    ],
    'mail' => [
        'class' => \yii\symfonymailer\Mailer::class,
        'viewPath' => '@app/mail',
    ]
];
