<?php

use app\models\StructuralRequirement;

return [
    'requirement_1' => [
        'id' => 1,
        'taskID' => 5022,
        'regexExpression' => '.*\.txt$',
        'type' => StructuralRequirement::SUBMISSION_EXCLUDES,
    ],
    'requirement_2' => [
        'id' => 2,
        'taskID' => 5023,
        'regexExpression' => '.*\.java$',
        'type' => StructuralRequirement::SUBMISSION_INCLUDES,
    ],
    'requirement_3' => [
        'id' => 3,
        'taskID' => 5024,
        'regexExpression' => '.*\.txt$',
        'type' => StructuralRequirement::SUBMISSION_INCLUDES,
    ],
    'requirement_4' => [
        'id' => 4,
        'taskID' => 5024,
        'regexExpression' => '/dir/',
        'type' => StructuralRequirement::SUBMISSION_EXCLUDES,
    ],
];
