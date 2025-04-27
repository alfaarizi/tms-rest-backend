<?php

use app\models\StructuralRequirements;

return [
    'requirement_1' => [
        'id' => 1,
        'taskID' => 5022,
        'regexExpression' => '.*\.txt$',
        'type' => StructuralRequirements::SUBMISSION_EXCLUDES,
    ],
    'requirement_2' => [
        'id' => 2,
        'taskID' => 5023,
        'regexExpression' => '.*\.java$',
        'type' => StructuralRequirements::SUBMISSION_INCLUDES,
    ],
    'requirement_3' => [
        'id' => 3,
        'taskID' => 5024,
        'regexExpression' => '.*\.txt$',
        'type' => StructuralRequirements::SUBMISSION_INCLUDES,
    ],
    'requirement_4' => [
        'id' => 4,
        'taskID' => 5024,
        'regexExpression' => '.*dir\/.*',
        'type' => StructuralRequirements::SUBMISSION_EXCLUDES,
    ],
];
