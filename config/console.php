<?php

$db = require(__DIR__ . '/db.php');
$mailer = require(__DIR__ . '/mailer.php');
$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'tms-console',
    'language' => 'en-US',
    'timeZone' => 'Europe/Budapest',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'db' => $db,
        'mailer' => $mailer,
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'baseUrl' => $params['consoleUrl'],
            // Hide index.php
            'showScriptName' => false,
            // Use pretty URLs
            'enablePrettyUrl' => true,
            // Use strict rule parsing
            'enableStrictParsing' => false,
            'rules' => [
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'except' => ['app\*'],
                    'maskVars' => ['_POST.LoginForm.password'],
                ],
                [
                    'class' => 'yii\log\DbTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'categories' => ['app\*'],
                    'logVars' => [],
                ],
            ],
        ],
        'user' => [
            'class' => 'app\models\NeptunUser',
            'identityClass' => 'app\models\User',
            'enableSession' => false,
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            'defaultRoles' => [],
            // uncomment if you want to cache RBAC items hierarchy
            'cache' => 'cache',
        ],
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'en-US',
                    'fileMap' => [
                        'app' => 'app.php',
                        'app/error' => 'error.php',
                        'app/mail' => 'mail.php',
                    ],
                ],
            ],
        ],
    ],
    'params' => $params,
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
            'namespace' => 'app\tests\unit\fixtures',
            'templatePath' => '@app/tests/templates/fixtures',
            'fixtureDataPath' => '@app/tests/_data',
        ],
        'migrate' => [ // Migration controller.
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => [
                '@app/migrations',
                '@yii/rbac/migrations',
                '@yii/log/migrations',
            ],
            'migrationNamespaces' => [],
        ],
    ],
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
