<?php

return [
    'task1' => [
        'id' => 1,
        'name' => 'Task 1',
        'semesterID' => 2,
        'groupID' => 1,
        'hardDeadline' => '2021-03-08 10:00:00',
        'category' => 'Larger tasks',
        'description' => 'Description',
        'createrID' => 8,
        'isVersionControlled' => 0
    ],
    'task2' => [
        'id' => 2,
        'name' => 'Task 2',
        'semesterID' => 2,
        'groupID' => 1,
        'softDeadLine' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'hardDeadline' => date('Y-m-d H:i:s', strtotime('+2 day')),
        'available' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'category' => 'Smaller tasks',
        'description' => 'Description',
        'createrID' => 8,
        'isVersionControlled' => 0
    ],
    'task3' => [
        'id' => 3,
        'name' => 'Task 3',
        'semesterID' => 2,
        'groupID' => 1,
        'softDeadLine' => date('Y-m-d H:i:s', strtotime('-2 day')),
        'hardDeadline' => date('Y-m-d H:i:s', strtotime('+2 day')),
        'available' => null,
        'category' => 'Larger tasks',
        'description' => 'Description',
        'createrID' => 8,
        'isVersionControlled' => 0
    ],
    'task4' => [
        'id' => 4,
        'name' => 'Task 4',
        'semesterID' => 2,
        'groupID' => 1,
        'available' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'hardDeadline' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'category' => 'Exams',
        'description' => 'Description',
        'createrID' => 8,
        'isVersionControlled' => 0
    ],
    'task5' => [
        'id' => 5,
        'name' => 'Task 5',
        'semesterID' => 2,
        'groupID' => 2,
        'hardDeadline' => date('Y-m-d H:i:s', strtotime('+2 day')),
        'available' => null,
        'category' => 'Classwork tasks',
        'description' => 'Description',
        'createrID' => 7,
        'isVersionControlled' => 0
    ],
    'task6' => [
        'id' => 6,
        'name' => 'Task 6',
        'semesterID' => 1,
        'groupID' => 11,
        'hardDeadline' => '2020-12-08 10:00:00',
        'available' => null,
        'category' => 'Classwork tasks',
        'description' => 'Description',
        'createrID' => 8,
        'isVersionControlled' => 0
    ],
    'task7_canvas' => [
        'id' => 7,
        'name' => 'Task 7',
        'semesterID' => 2,
        'groupID' => 6,
        'hardDeadline' => date('Y-m-d H:i:s', strtotime('+2 day')),
        'available' => null,
        'category' => 'Smaller tasks',
        'description' => 'Description',
        'createrID' => 8,
        'isVersionControlled' => 0
    ],
    'task8' => [
        'id' => 8,
        'name' => 'Task 8',
        'semesterID' => 2,
        'groupID' => 8,
        'hardDeadline' => date('Y-m-d H:i:s', strtotime('+2 day')),
        'available' => null,
        'category' => 'Smaller tasks',
        'description' => 'Description',
        'createrID' => 8,
        'isVersionControlled' => 0
    ],
    'task9' => [
        'id' => 9,
        'name' => 'Task 9',
        'semesterID' => 2,
        'groupID' => 1,
        'softDeadLine' => date('Y-m-d H:i:s', strtotime('-2 day')),
        'hardDeadline' => date('Y-m-d H:i:s', strtotime('+2 day')),
        'available' => null,
        'category' => 'Larger tasks',
        'description' => 'Description',
        'createrID' => 8,
        'isVersionControlled' => 0
    ],
];
