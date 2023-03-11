<?php

return [
    // Directory for data storage (uploadedfiles, tmp, plagiarism)
    'data_dir' => 'appdata',
    // Administrator email address
    'adminEmail' => 'admin@example.com',
    // Notification sender email address
    'systemEmail' => 'noreply@example.com',
    // Console commands cannot auto-detect host base URL, this will be used
    'backendUrl' => 'http://localhost/backend-core/web',
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
                //reserved ports for on windows docker host
                'reservedPorts' => ['9090', '9091']
            ]
        ],
        // preconfigured templates
        'templates' => [
            [
                'name' => 'Linux / gcc',
                'os' => 'linux',
                'appType' => 'Console',
                'image' => 'mcserep/elte:ubuntu-2004',
                'compileInstructions' =>
                    '# Remove spaces from directory and file names' . PHP_EOL .
                    'find -name "* *" -type d | rename \'s/ /_/g\'' . PHP_EOL .
                    'find -name "* *" -type f | rename \'s/ /_/g\'' . PHP_EOL .
                    '# Build the program' . PHP_EOL .
                    'CFLAGS="std=c11 -pedantic -W -Wall -Wextra"' . PHP_EOL .
                    'gcc $CFLAGS $(find . -type f -iname "*.c") -o program.out',
                'runInstructions' => './program.out "$@"',
            ],
            [
                'name' => 'Linux / g++',
                'os' => 'linux',
                'appType' => 'Console',
                'image' => 'mcserep/elte:ubuntu-2004',
                'autoTest' => true,
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
                'appType' => 'Console',
                'image' => 'mcserep/elte:ubuntu-2004-qt5',
                'autoTest' => true,
                'compileInstructions' => '/build.sh' . PHP_EOL .
                    '# Built-in script that looks for Qt projects (Qt Creator, CMake) and build them.',
                'runInstructions' => '',
            ],
            [
                'name' => 'Linux / .NET',
                'os' => 'linux',
                'appType' => 'Console',
                'image' => 'mcserep/elte:dotnet-60',
                'compileInstructions' => '/build.sh' . PHP_EOL .
                    '# Built-in script that looks for .NET Core projects (.sln files) and build them.',
                'runInstructions' => '/execute.sh' . PHP_EOL .
                    '# Built-in script that looks for executable .NET Core projects and runs the first one.',
            ],
            [
                'name' => 'Windows / .NET',
                'os' => 'windows',
                'appType' => 'Console',
                'image' => 'mcserep/elte:dotnet-60',
                'autoTest' => true,
                'compileInstructions' => 'C:\\build.ps1' . PHP_EOL .
                    '# Built-in script that looks for .NET Core projects (.sln files) and build them.',
                'runInstructions' => 'C:\\execute.ps1' . PHP_EOL .
                    '# Built-in script that looks for executable .NET Core projects and runs the first one.',
            ],
            [
                'name' => 'Windows / .NET + MAUI',
                'os' => 'windows',
                'appType' => 'Console',
                'image' => 'mcserep/elte:dotnet-60-maui',
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
    // CodeCompass integration configuration
    'codeCompass' => [
        'enabled' => true,
        'socket' => 'unix:///var/run/docker.sock',
        'imageName' => 'modelcpp/codecompass:runtime-sqlite',
        'maxContainerNum' => 10,
        'containerExpireMinutes' => 30,
        'portRange' => [6200, 6300],
        'username' => 'compass',
        'passwordLength' => 6,
        'isImageCachingEnabled' => true
    ],
    // Cronjob scheduling configuration
    'scheduling' => [
        'periods' => [
            'canvasSynchronizePrioritized' => 5, // in minutes
            'systemClearExpiredAccessTokens' => 7, // in days
            'autoTesterCheck' => 5, // in minutes
            'ccClearCachedImages' => 30, // in days
            'ccStartWaitingContainer' => 10, // in minutes
            'ccStopExpiredContainers' => 10, // in minutes
            'waShutDownExpiredExecutions' => 10, // in minutes
        ],
        'dates' => [ // 0:00 - 23:59
            'nDigestInstructors' => '7:00',
            'nDigestOncomingTaskDeadlines' => '7:00',
        ],
        'params' => [
            'autoTesterCheckTasksNumber' => 50,
            'canvasSynchronizePrioritizedNumber' => 5,
        ],
    ],
];
