<?php

$db = require(__DIR__ . '/db.php');
$mailer = require(__DIR__ . '/mailer.php');
$params = require(__DIR__ . '/params.php');
$rules = require(__DIR__ . '/rules.php');

$config = [
    'id' => 'tms',
    'language' => 'hu',
    'timeZone' => 'Europe/Budapest',
    'basePath' => dirname(__DIR__),
    'bootstrap' => [
        'log',
        'student',
        'instructor',
        'admin'
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'db' => $db,
        'request' => [
            'enableCsrfCookie' => false,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            'cookieValidationKey' => 'almaf4rkat4rkahamarha',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'assetManager' => [
            'appendTimestamp' => true,
        ],
        'user' => [
            'class' => 'app\models\NeptunUser',
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null,
            //'authTimeout' => 60,
            //'identityCookie' => [
            //    'name' => '_devUser', // unique for env
            //    'path'=>'/dev'  // correct path for the env.
            //]
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            'defaultRoles' => [],
            // uncomment if you want to cache RBAC items hierarchy
            'cache' => 'cache',
        ],
        'session' => [
            'name' => '_devSessionId', // unique for env
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => $mailer,
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            // Hide index.php
            'showScriptName' => false,
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
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'except' => ['app\*'],
                    'maskVars' => ['_POST.password'],
                ],
                [
                    'class' => 'yii\log\DbTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'categories' => ['app\*'],
                    'logVars' => [],
                    'prefix' => function () {
                        // Get ip address
                        $ip = Yii::$app->request->getUserIP();

                        // Get user identity
                        $identity = Yii::$app->user->identity;
                        $userString = !is_null($identity) ? "$identity->name ($identity->neptun)" : "-";

                        return "[$ip][$userString]";
                    }
                ],
            ],
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
    'container' => [
        'definitions' => [
            'Docker\Docker' => function ($container, $params, $config) {
                return Docker\Docker::create(
                    Docker\DockerClientFactory::create(
                        [
                            'remote_socket' => Yii::$app->params['evaluator'][$params['os']]
                        ]
                    )
                );
            },
            \app\components\SubmissionRunner::class => \app\components\SubmissionRunner::class,
        ]
    ],
];

if (YII_ENV_PROD && isset($_SERVER['HOME'])) {
    $config['aliases']['@simplesamlphp'] = "${_SERVER['HOME']}/samlsrc/simplesamlphp/";
}

/*
 * This section for development mode.
 * DO NOT enable it in published version.
 */
if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['components']['swagger'] = [
        'class' => 'app\components\openapi\SchemaGenerator',
        'outputDir' => '@app/runtime/openapi-schemas/',
        // Scanned namespaces
        'namespaces' => [
            // Prefix => Namespace
            'Common' => 'app\\resources',
            'Student' => 'app\\modules\\student\\resources',
            'Instructor' => 'app\\modules\\instructor\\resources',
        ],
    ];
}

return $config;
