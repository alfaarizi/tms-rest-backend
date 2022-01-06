<?php

return [
    // Directory for data storage (uploadedfiles, tmp, plagiarism)
    'data_dir' => 'appdata',
    // Administrator email address
    'adminEmail' => 'admin@example.com',
    // Notification sender email address
    'systemEmail' => 'noreply@example.com',
    // Console commands cannot auto-detect host base URL, this will be used
    'consoleUrl' => 'http://localhost',
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
    // LDAP authentication parameters
    'ldap' => [
        'host' => 'ldap://mydomain.com:389',
        'baseDN' => 'OU=...,DC=...,DC=...,DC=...',
        'bindDN' => 'CN=...,OU=...,DC=...,DC=...,DC=...',
        'bindPasswd' => 'bindPassword',
        'uidAttr' => 'sAMAccountName',
    ],
    // Version control configuration
    'versionControl' => [
        'enabled' => false,
        // Path of the shell executable
        'shell' => '#!/bin/bash; C:/Program\ Files/Git/usr/bin/sh.exe',
        // Base path of web access
        'basePath' => '/git/',
    ],
    // Automated evaluator configuration
    'evaluator' => [
        'enabled' => false,
        // Linux-based docker host
        'linux' => 'unix:///var/run/docker.sock',
        // Windows-based docker host
        'windows' => '',
        // seconds allowed to compile a submission
        'compileTimeout' => '60',
        // seconds allowed to run a test case
        'testTimeout' => '5',
        // preconfigured templates
        'templates' => [
            [
                'name' => 'Linux / g++',
                'os' => 'linux',
                'image' => 'mcserep/elte:ubuntu-2004',
                'compileInstructions' =>
                    '# Remove spaces from directory and file names' . PHP_EOL .
                    'find -name "* *" -type d | rename \'s/ /_/g\'' . PHP_EOL .
                    'find -name "* *" -type f | rename \'s/ /_/g\'' . PHP_EOL .
                    '# Build the program' . PHP_EOL .
                    'CFLAGS="-std=c++14 -pedantic -Wall -I ./include"' . PHP_EOL .
                    'g++ $CFLAGS $(find . -type f -iname "*.cpp") -o program.out',
                'runInstructions' => './program.out "$@"',
            ],
            [
                'name' => 'Linux / Qt5',
                'os' => 'linux',
                'image' => 'mcserep/elte:ubuntu-2004-qt5',
                'compileInstructions' => '/build.sh' . PHP_EOL .
                    '# Built-in script that looks for Qt projects (Qt Creator, CMake) and build them.',
                'runInstructions' => '',
            ],
            [
                'name' => 'Windows / .NET',
                'os' => 'windows',
                'image' => 'mcserep/elte:dotnet-48',
                'compileInstructions' => 'C:\\build.ps1' . PHP_EOL .
                    '# Built-in script that looks for .NET Core projects (.sln files) and build them.',
                'runInstructions' => '',
            ],
        ]
    ],
    // Canvas synchronization configuration
    'canvas' => [
        'enabled' => false,
        'url' => '',
        'clientID' => '',
        'secretKey' => '',
        'redirectUri' => ''
    ],
];
