<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=<db host name>;dbname=<database name>',
    'username' => '<db user>',
    'password' => '<db password>',
    'charset' => 'utf8',
    'tablePrefix' => '',

    // Schema cache options (for production environment)
    'enableSchemaCache' => YII_ENV_PROD,
];
