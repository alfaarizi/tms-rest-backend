<?php

return [
    'notification0' => [
        'id' => 4000,
        'message' => 'Test message 1.',
        'startTime' => '2020-01-01 00:00:00',
        'endTime' => '3023-12-05 00:00:00',
        'dismissable' => true,
        'scope' => \app\models\Notification::SCOPE_EVERYONE,
    ],
    'notification1' => [
        'id' => 4001,
        'message' => 'Test message 2.',
        'startTime' => '2020-01-01 00:00:00',
        'endTime' => '2020-01-02 00:00:00',
        'dismissable' => false,
        'scope' => \app\models\Notification::SCOPE_EVERYONE,
    ],
    'notification2' => [
        'id' => 4002,
        'message' => 'Test message 3.',
        'startTime' => '2020-01-01 00:00:00',
        'endTime' => '3023-12-05 00:00:00',
        'dismissable' => true,
        'scope' => \app\models\Notification::SCOPE_USER,
    ],
    'notification3' => [
        'id' => 4003,
        'message' => 'Test message 4.',
        'startTime' => '3020-01-01 00:00:00',
        'endTime' => '3020-01-02 00:00:00',
        'dismissable' => false,
        'scope' => \app\models\Notification::SCOPE_USER,
    ],
    'notification4' => [
        'id' => 4004,
        'message' => 'Test message 5.',
        'startTime' => '2020-01-01 00:00:00',
        'endTime' => '3023-12-05 00:00:00',
        'dismissable' => true,
        'scope' => \app\models\Notification::SCOPE_STUDENT,
    ],
    'notification5' => [
        'id' => 4005,
        'message' => 'Test message 6.',
        'startTime' => '2020-01-01 00:00:00',
        'endTime' => '3020-01-02 00:00:00',
        'dismissable' => true,
        'scope' => \app\models\Notification::SCOPE_FACULTY,
    ],
];
