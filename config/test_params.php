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
        // preconfigured templates
        'templates' => [],
    ],
    // Canvas synchronization configuration
    'canvas' => [
        'enabled' => false,
    ],
];
