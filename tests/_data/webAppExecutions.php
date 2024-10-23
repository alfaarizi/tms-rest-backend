<?php

return [
    'execution_1' => [
        'id' => 1,
        'submissionID' => 1,
        'instructorID' => 1006,
        'dockerHostUrl' => 'https://tms.elte.hu',
        'port' => 8080,
        'containerName' => 'agnosticWhale',
        'startedAt' => '2021-03-08 10:00:00',
        'shutdownAt' => '2021-03-08 11:00:00'
    ],
    'execution_2' => [
        'id' => 2,
        'submissionID' => 2,
        'instructorID' => 1006,
        'dockerHostUrl' => 'https://tms.elte.hu',
        'port' => 8089,
        'containerName' => 'agnosticWhale',
        'startedAt' => '2021-03-08 10:00:00',
        'shutdownAt' => date('Y-m-d H:i:s', strtotime('+2 day'))
    ],
];
