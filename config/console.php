<?php

$db = require(__DIR__ . '/db.php');
$mailer = require(__DIR__ . '/mailer.php');
$params = require(__DIR__ . '/params.php');

$params['backendUrl'] = rtrim($params['backendUrl'], '/');
$params['frontendUrl'] = rtrim($params['frontendUrl'], '/');
if ($params['versionControl']['enabled']) {
    $params['versionControl']['basePath'] = rtrim($params['versionControl']['basePath'], '/');
}

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
            'baseUrl' => $params['backendUrl'],
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
                    'maskVars' => ['_POST.password'],
                ],
                [
                    'class' => 'yii\log\DbTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'categories' => ['app\*'],
                    'logVars' => [],
                    'prefix' => function () {
                        return "[console][-]";
                    }
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
        'swagger' => [
            'class' => 'app\components\openapi\SchemaGenerator',
            'outputDir' => '@app/runtime/openapi_schemas/',
            // Scanned namespaces
            'namespaces' => [
                // Prefix => Namespace
                'Common' => 'app\\resources',
                'Student' => 'app\\modules\\student\\resources',
                'Instructor' => 'app\\modules\\instructor\\resources',
            ],
        ]
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
    'container' => [
        'definitions' => [
            \Docker\Docker::class => function ($container, $params, $config) {
                return Docker\Docker::create(
                    Docker\DockerClientFactory::create(
                        [
                            'remote_socket' => Yii::$app->params['evaluator'][$params['os']]
                        ]
                    )
                );
            },
            \app\components\docker\DockerImageManager::class => function ($container, $params, $config) {
                return new \app\components\docker\DockerImageManager($params['os']);
            },
            \app\components\SubmissionRunner::class => \app\components\SubmissionRunner::class,
            \app\components\CanvasIntegration::class => \app\components\CanvasIntegration::class,
            \app\components\codechecker\AnalyzerRunner::class => [\app\components\codechecker\AnalyzerRunnerFactory::class, 'createForStudentFile'],
            \app\components\codechecker\CodeCheckerResultPersistence::class => function ($container, $params, $config) {
                return new \app\components\codechecker\CodeCheckerResultPersistence($params['studentFile']);
            },
            \app\components\codechecker\CodeCheckerResultNotifier::class => \app\components\codechecker\CodeCheckerResultNotifier::class,
        ]
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
