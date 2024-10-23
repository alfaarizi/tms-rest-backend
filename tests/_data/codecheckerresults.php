<?php

return [
    'file1_result1' => [
        'id' => 1,
        'token' => 'token',
        'submissionId' => 1,
        'createdAt' => date('Y-m-d H:i:s'),
        'status' => \app\models\CodeCheckerResult::STATUS_ISSUES_FOUND,
    ],
    'file6_result1' => [
        'id' => 2,
        'token' => 'token',
        'submissionId' => 6,
        'createdAt' => date('Y-m-d H:i:s'),
        'status' => \app\models\CodeCheckerResult::STATUS_IN_PROGRESS,
    ],
    'file17_result1' => [
        'id' => 3,
        'token' => 'token',
        'submissionId' => 17,
        'createdAt' => date('Y-m-d H:i:s'),
        'status' => \app\models\CodeCheckerResult::STATUS_ANALYSIS_FAILED,
    ]
];
