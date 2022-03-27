<?php

return [
    // Directory for data storage (uploadedfiles, tmp, plagiarism)
    'data_dir' => 'appdata_test',
    // Administrator email address
    'adminEmail' => 'admin@example.com',
    // Notification sender email address
    'systemEmail' => 'noreply@example.com',
    // Console commands cannot auto-detect host base URL, this will be used
    'backendUrl' => 'http://localhost',
    // Frontend application base URL
    'frontendUrl' => 'http://localhost:3000/app',
    // Moss user id for plagiarism detection
    'mossId' => '',
    // Google Analytics tracking ID
    'googleAnalyticsId' => '',
    // Supported localizations
    'supportedLocale' => [
        'en-US' => 'English',
        'hu' => 'Magyar',
    ],
    // AccessToken validation will be extended with this value
    'accessTokenExtendValidationBy' => '30 minutes',
    // Version control configuration
    'versionControl' => [
        'enabled' => false
    ],
    // Automated evaluator configuration
    'evaluator' => [
        'enabled' => true,
        // Linux-based docker host
        'linux' => 'unix:///var/run/docker.sock',
        // Windows-based docker host
        'windows' => '',
        // seconds allowed to compile a submission
        'compileTimeout' => '60',
        // seconds allowed to run a test case
        'testTimeout' => '5',
        //web app execution configuration
        'webApp' => [
            // ttl of remote web applications
            'maxWebAppRunTime' => '60',
            //web app access reverse proxy configuration
            'gateway' => [
                //gateway configured
                'enabled' => false,
                //gateway url
                'url' => ''
            ],
            'linux' => [
                //reserved ports for on linux docker host
                'reservedPorts'  => ['8080', '8089']
            ],
            'windows' => [
                //reserved ports for on linux docker host
                'reservedPorts' => ['9090', 9091]
            ]
        ],
        // preconfigured templates
        'templates' => [],
    ],
    // Canvas synchronization configuration
    'canvas' => [
        'enabled' => true,
        'url' => 'https://canvas.example.com/',
        'clientID' => '1',
        'secretKey' => 'key',
        'redirectUri' => 'http://localhost:3000/instructor/task-mamager/canvas/oauth2-response'
    ],
    // CodeCompass integration configuration
    'codeCompass' => [
        'enabled' => true,
        'socket' => 'unix:///var/run/docker.sock',
        'imageName' => 'modelcpp/codecompass:runtime-sqlite',
        'maxContainerNum' => 3,
        'containerExpireMinutes' => 61,
        'portRange' => [25565, 25568],
        'username' => 'compass',
        'passwordLength' => 6,
        'isImageCachingEnabled' => true
    ]
];
