<?php

return [
    "token0" => [
        "token" => "BATMAN;12345",
        "userID" => 1000,
        "validUntil" => date('Y-m-d H:i:s', time() + 1200),
        "imageToken" => "BATMAN;1234",
    ],
    "token1" => [
        "token" => "STUD01;EXPIRED",
        "userID" => 1001,
        "validUntil" => date('Y-m-d H:i:s', time() - 1200),
        "imageToken" => "STUD01;EXPIRED",
    ],
    "token2" => [
        "token" => "STUD01;VALID",
        "userID" => 1001,
        "validUntil" => date('Y-m-d H:i:s', time() + 1200),
        "imageToken" => "STUD01;VALID",
    ],
    "token3" => [
        "token" => "STUD02;VALID",
        "userID" => 1002,
        "validUntil" => date('Y-m-d H:i:s', time() + 1200),
        "imageToken" => "STUD02;VALID",
    ],
    "token4" => [
        "token" => "TEACH1;VALID",
        "userID" => 1006,
        "validUntil" => date('Y-m-d H:i:s', time() + 1200),
        "imageToken" => "TEACH1;VALID",
    ],
    "token5" => [
        "token" => "TEACH2;VALID",
        "userID" => 1007,
        "validUntil" => date('Y-m-d H:i:s', time() + 1200),
        "imageToken" => "ADMIN1;VALID",
    ],
];
