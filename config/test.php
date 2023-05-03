<?php

$db = require(__DIR__ . '/test_db.php');
$params = require(__DIR__ . '/test_params.php');
$rules = require(__DIR__ . '/rules.php');
$di = require(__DIR__ . '/di.php');
$di['definitions'] = array_merge($di['definitions'], [
    \Docker\Docker::class => \app\tests\doubles\DockerStub::class,
    \app\components\plagiarism\AbstractPlagiarismFinder::class => \app\tests\doubles\NoopPlagiarismFinder::class,
]);

/**
 * Application configuration shared by all test types
 */
return [
    'id' => 'tms-tests',
    'language' => 'en-US',
    'timeZone' => 'Europe/Budapest',
    'basePath' => dirname(__DIR__),
    'bootstrap' => [
        'student',
        'instructor',
        'admin'
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'db' => $db,
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            // Message class required by CodeCeption
            'messageClass' => \yii\symfonymailer\Message::class,
            'viewPath' => '@app/mail',
            'useFileTransport' => true,
        ],
        'assetManager' => [
            'basePath' => __DIR__ . '/../web/assets',
        ],
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            // Don't hide index-test.php
            'showScriptName' => true,
            // Use pretty URLs
            'enablePrettyUrl' => true,
            // Use strict rule parsing
            'enableStrictParsing' => true,
            // Removes trailing slashes
            'normalizer' => [
                'class' => 'yii\web\UrlNormalizer',
                // use temporary redirection instead of permanent for debugging
                'action' => YII_DEBUG
                    ? yii\web\UrlNormalizer::ACTION_REDIRECT_TEMPORARY
                    : yii\web\UrlNormalizer::ACTION_REDIRECT_PERMANENT
            ],

            'rules' => $rules,
        ],
        'user' => [
            'class' => 'app\models\NeptunUser',
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null,
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            'defaultRoles' => [],
            // uncomment if you want to cache RBAC items hierarchy
            //'cache' => 'cache',
        ],
        'request' => [
            'cookieValidationKey' => 'test',
            'enableCsrfValidation' => false,
            // but if you absolutely need it set cookie domain to localhost
            /*
            'csrfCookie' => [
                'domain' => 'localhost',
            ],
            */
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
        ]
    ],
    'modules' => [
        'student' => [
            'class' => 'app\modules\student\Module',
        ],
        'instructor' => [
            'class' => 'app\modules\instructor\Module',
        ],
        'admin' => [
            'class' => 'app\modules\admin\Module',
        ],
    ],
    'params' => $params,
    'container' => $di,
];
