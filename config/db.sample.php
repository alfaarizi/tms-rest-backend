<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=<db host neve>;dbname=<db neve>',
    'username' => '<db felhasznalo>',
    'password' => '<db jelszo>',
    'charset' => 'utf8',
    'tablePrefix' => '',

    // Schema cache options (for production environment)
    'enableSchemaCache' => YII_ENV_PROD,
];
