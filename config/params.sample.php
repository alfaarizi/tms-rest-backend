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
        // seconds allowed to perform static analysis
        'staticAnalysisTimeout' => '300',
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
                // Environment
                'name' => 'Linux / gcc',
                'os' => 'linux',
                'image' => 'tmselte/evaluator:gcc-ubuntu-20.04',
                // Auto Test
                'autoTest' => true,
                'appType' => 'Console',
                'compileInstructions' =>
                    '# Remove spaces from directory and file names' . PHP_EOL .
                    'find -name "* *" -type d | rename \'s/ /_/g\'' . PHP_EOL .
                    'find -name "* *" -type f | rename \'s/ /_/g\'' . PHP_EOL .
                    '# Build the program' . PHP_EOL .
                    'CFLAGS="-std=c11 -pedantic -W -Wall -Wextra"' . PHP_EOL .
                    'gcc $CFLAGS $(find . -type f -iname "*.c") -o program.out',

                'runInstructions' => './program.out "$@"',
                // Static Code Analysis
                'staticCodeAnalysis' => true,
                'staticCodeAnalyzerTool' => 'codechecker',
                'codeCheckerCompileInstructions' =>
                    '# Remove spaces from directory and file names' . PHP_EOL .
                    'find -name "* *" -type d | rename \'s/ /_/g\'' . PHP_EOL .
                    'find -name "* *" -type f | rename \'s/ /_/g\'' . PHP_EOL .
                    '# Build the program' . PHP_EOL .
                    'CFLAGS="-std=c11 -pedantic -W -Wall -Wextra"' . PHP_EOL .
                    'gcc $CFLAGS $(find . -type f -iname "*.c") -o program.out',
                'codeCheckerSkipFile' => '-/usr/*',
                'codeCheckerToggles' => '',
            ],
            [
                // Environment
                'name' => 'Linux / g++',
                'os' => 'linux',
                'image' => 'tmselte/evaluator:gcc-ubuntu-20.04',
                // Auto Test
                'autoTest' => true,
                'appType' => 'Console',
                'compileInstructions' =>
                    '# Remove spaces from directory and file names' . PHP_EOL .
                    'find -name "* *" -type d | rename \'s/ /_/g\'' . PHP_EOL .
                    'find -name "* *" -type f | rename \'s/ /_/g\'' . PHP_EOL .
                    '# Build the program' . PHP_EOL .
                    'CFLAGS="-std=c++14 -pedantic -Wall -I ./include"' . PHP_EOL .
                    'g++ $CFLAGS $(find . -type f -iname "*.cpp") -o program.out',
                'runInstructions' => './program.out "$@"',
                // Static Code Analysis
                'staticCodeAnalysis' => true,
                'staticCodeAnalyzerTool' => 'codechecker',
                'codeCheckerCompileInstructions' =>
                    '# Remove spaces from directory and file names' . PHP_EOL .
                    'find -name "* *" -type d | rename \'s/ /_/g\'' . PHP_EOL .
                    'find -name "* *" -type f | rename \'s/ /_/g\'' . PHP_EOL .
                    '# Build the program' . PHP_EOL .
                    'CFLAGS="-std=c++14 -pedantic -Wall -I ./include"' . PHP_EOL .
                    'g++ $CFLAGS $(find . -type f -iname "*.cpp") -o program.out',
                'codeCheckerSkipFile' => '-/usr/*',
                'codeCheckerToggles' => '',
            ],
            [
                // Environment
                'name' => 'Linux / Qt5',
                'os' => 'linux',
                'image' => 'tmselte/evaluator:qt5-ubuntu-20.04',
                // Auto Test
                'autoTest' => true,
                'appType' => 'Console',
                'compileInstructions' => '/build.sh' . PHP_EOL .
                    '# Built-in script that looks for Qt projects (Qt Creator, CMake) and build them.',
                'runInstructions' => '',
                // Static Code Analysis
                'staticCodeAnalysis' => true,
                'staticCodeAnalyzerTool' => 'codechecker',
                'codeCheckerCompileInstructions'  => '/build.sh' . PHP_EOL .
                    '# Built-in script that looks for Qt projects (Qt Creator, CMake) and build them.',
                'codeCheckerSkipFile' =>
                    '-/usr/*' . PHP_EOL .
                    '-*/moc*' . PHP_EOL .
                    '-*/qrc*',
                'codeCheckerToggles' => '',
            ],
            [
                // Environment
                'name' => 'Linux / .NET',
                'os' => 'linux',
                'appType' => 'Console',
                'image' => 'tmselte/evaluator:dotnet-6.0',
                // Auto Test
                'autoTest' => true,
                'compileInstructions' => '/build.sh' . PHP_EOL .
                    '# Built-in script that looks for .NET Core projects (.sln files) and build them.',
                'runInstructions' => '/execute.sh' . PHP_EOL .
                    '# Built-in script that looks for executable .NET Core projects and runs the first one.',
                // Static Code Analysis
                'staticCodeAnalysis' => true,
                'staticCodeAnalyzerTool' => 'roslynator',
                'staticCodeAnalyzerInstructions' =>
                    'set -e' . PHP_EOL .
                    "IFS=$'\\n'" . PHP_EOL .
                    'counter=$(find . -iname "*.csproj" | wc -l)' . PHP_EOL .
                    'if [ $counter -eq 0 ]; then' . PHP_EOL .
                    '    echo "No Visual Studio projects found." 1>&2' . PHP_EOL .
                    '    exit 1' . PHP_EOL .
                    'fi' . PHP_EOL .
                    'diagnostics=("--supported-diagnostics")' . PHP_EOL .
                    'if [ -f /test/test_files/diagnostics.txt ]; then' . PHP_EOL .
                    '    readarray  -t -O "${#diagnostics[@]}" diagnostics < <(grep -v "^#" /test/test_files/diagnostics.txt)' . PHP_EOL .
                    'else' . PHP_EOL .
                    '    readarray  -t -O "${#diagnostics[@]}" diagnostics < <(curl --fail --silent --show-error https://gitlab.com/tms-elte/backend-core/-/snippets/2518152/raw/main/diagnostics.txt | grep -v "^#")' . PHP_EOL .
                    'fi' . PHP_EOL .
                    'roslynator analyze $(find . -name "*.csproj") \\' . PHP_EOL .
                    '    --output roslynator.xml \\' . PHP_EOL .
                    '    --severity-level hidden \\' . PHP_EOL .
                    '    --analyzer-assemblies $ANALYZERS_DIR \\' . PHP_EOL .
                    '    --ignore-analyzer-references \\' . PHP_EOL .
                    '    --report-suppressed-diagnostics \\' . PHP_EOL .
                    '    "${diagnostics[@]}"' . PHP_EOL .
                    'roslynatorExitCode=$?' . PHP_EOL .
                    'if [ -f roslynator.xml ]; then' . PHP_EOL .
                    '    exit 1' . PHP_EOL .
                    'fi' . PHP_EOL .
                    'exit $roslynatorExitCode' . PHP_EOL
                ,
                'codeCheckerSkipFile' => '',
            ],
            [
                // Environment
                'name' => 'Windows / .NET',
                'os' => 'windows',
                'image' => 'tmselte/evaluator:dotnet-6.0',
                // Auto Test
                'autoTest' => true,
                'appType' => 'Console',
                'compileInstructions' => 'C:\\build.ps1' . PHP_EOL .
                    '# Built-in script that looks for .NET Core projects (.sln files) and build them.',
                'runInstructions' => 'C:\\execute.ps1' . PHP_EOL .
                    '# Built-in script that looks for executable .NET Core projects and runs the first one.',
                // Static Code Analysis
                'staticCodeAnalysis' => true,
                'staticCodeAnalyzerTool' => 'roslynator',
                'staticCodeAnalyzerInstructions' =>
                    '$ErrorActionPreference = "Stop"' . PHP_EOL .
                    '$projects = Get-ChildItem -Path .\ -Filter *.csproj -Recurse -File -Name' . PHP_EOL .
                    'if ($projects.Count -eq 0) {' . PHP_EOL .
                    '    Write-Error -Message "No C# projects found." -Category InvalidData' . PHP_EOL .
                    '    exit 1' . PHP_EOL .
                    '}' . PHP_EOL .
                    '$diagnostics = @("--supported-diagnostics")' . PHP_EOL .
                    'if (Test-Path -Path C:\\test\\test_files\\diagnostics.txt) {' . PHP_EOL .
                    '    $diagnostics += Get-Content -Path C:\\test\\test_files\\diagnostics.txt | Where-Object { $_ -notmatch "^#" }' . PHP_EOL .
                    '} else {' . PHP_EOL .
                    '    $diagnostics += curl.exe --fail --silent --show-error https://gitlab.com/tms-elte/backend-core/-/snippets/2518152/raw/main/diagnostics.txt | Where-Object { $_ -notmatch "^#" }' . PHP_EOL .
                    '}' . PHP_EOL .
                    'roslynator analyze $projects `' . PHP_EOL .
                    '    --output roslynator.xml `' . PHP_EOL .
                    '    --severity-level hidden `' . PHP_EOL .
                    '    --ignore-analyzer-references `' . PHP_EOL .
                    '    --analyzer-assemblies $env:ANALYZERS_DIR `' . PHP_EOL .
                    '    --report-suppressed-diagnostics `' . PHP_EOL .
                    '    $diagnostics' . PHP_EOL .
                    '$roslynatorExitCode = $LASTEXITCODE' . PHP_EOL .
                    'if (Test-Path -Path "roslynator.xml") {' . PHP_EOL .
                    '    exit 1' . PHP_EOL .
                    '}' . PHP_EOL .
                    'exit $roslynatorExitCode' . PHP_EOL
                ,
                'codeCheckerSkipFile' => '',
            ],
            [
                // Environment
                'name' => 'Windows / .NET + MAUI',
                'os' => 'windows',
                // Auto Test
                'autoTest' => true,
                'appType' => 'Console',
                'image' => 'tmselte/evaluator:maui-6.0-windows',
                'compileInstructions' => 'C:\\build.ps1' . PHP_EOL .
                    '# Built-in script that looks for .NET Core projects (.sln files) and build them.',
                'runInstructions' => '',
                // Static Code Analysis
                'staticCodeAnalysis' => false,
            ],
        ],
        'supportedStaticAnalyzerTools' => [
            // Template for tool configuration
            // The key should be the tool name that will be passed to the 'report-converter' command with the '-t' flag
            // List of the supported tools: https://github.com/Ericsson/codechecker/blob/master/docs/tools/report-converter.md
            // 'sample_key' => [
            //     'title' => 'Display name on the frontend',
            //     // Path to the analyzer results relative to 'test' directory of the container
            //     // Expected path separator: '/'
            //    'outputPath' => 'folder/output'
            // ],
            'roslynator' => [
                'title' => 'Roslynator (C#)',
                'outputPath' => 'submission/roslynator.xml'
            ],
        ],
        'reportConverterImage' => [
            'linux' => 'tmselte/codechecker:6',
            'windows' => 'tmselte/codechecker:6',
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
            'codeCheckerCheck' => 5, // in minutes
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
            'codeCheckerCheckSubmissionsNumber' => 50,
            'canvasSynchronizePrioritizedNumber' => 5,
        ],
    ],
];
