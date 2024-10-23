<?php

return [
    'codecompassinstance1' => [
        'id' => 1,
        'submissionId' => 1,
        'instanceStarterUserId' => 1006,
        'port' => '25565',
        'containerId' => 'compass_1',
        'status' => \app\models\CodeCompassInstance::STATUS_RUNNING,
        'errorLogs' => '',
        'creationTime' => date('Y-m-d H:i:s'),
        'username' => 'TMS',
        'password' => '2344242'
    ],
    'codecompassinstance2' => [
        'id' => 2,
        'submissionId' => 2,
        'instanceStarterUserId' => 1006,
        'port' => '25566',
        'containerId' => 'compass_2',
        'status' => \app\models\CodeCompassInstance::STATUS_STARTING,
        'errorLogs' => '',
        'creationTime' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        'username' => 'TMS',
        'password' => '2344242'
    ],
    'codecompassinstance3' => [
        'id' => 3,
        'submissionId' => 5,
        'instanceStarterUserId' => 1006,
        'port' => '25567',
        'containerId' => 'compass_3',
        'status' => \app\models\CodeCompassInstance::STATUS_RUNNING,
        'errorLogs' => '',
        'creationTime' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'username' => 'TMS',
        'password' => '2344242'
    ],
    'codecompassinstance4' => [
        'id' => 4,
        'submissionId' => 2,
        'instanceStarterUserId' => 1006,
        'port' => null,
        'containerId' => null,
        'status' => \app\models\CodeCompassInstance::STATUS_WAITING,
        'errorLogs' => '',
        'creationTime' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        'username' => 'TMS',
        'password' => '2344242'
    ],
    'codecompassinstance5' => [
        'id' => 5,
        'submissionId' => 2,
        'instanceStarterUserId' => 1006,
        'port' => null,
        'containerId' => null,
        'status' => \app\models\CodeCompassInstance::STATUS_WAITING,
        'errorLogs' => '',
        'creationTime' => date('Y-m-d H:i:s', strtotime('-2 hour')),
        'username' => 'TMS',
        'password' => '2344242'
    ],
];
