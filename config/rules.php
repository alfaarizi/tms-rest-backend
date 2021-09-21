<?php

return [
    "/" => "site/index",

    // Keep the old examination image path for compatibility
    "examination/image/<id>/<filename>" => 'images/view-exam-image',
    // Keep the old git path for compatibility
    "git/<action>" => "git/<action>",

    'GET,HEAD common/<controller>' => '<controller>/index',
    'POST common/<controller>' => '<controller>/create',
    'GET,HEAD common/<controller>/<id:\d+>' => '<controller>/view',
    'PUT,PATCH common/<controller>/<id:\d+>' => '<controller>/update',
    'DELETE common/<controller>/<id:\d+>' => '<controller>/delete',
    'OPTIONS common/<controller>' => '<controller>/options',
    'OPTIONS common/<controller>/<wildcard:.*>' => '<controller>/options',
    'common/<controller>/<action>' => '<controller>/<action>',

    'GET,HEAD <module>/<controller>' => '<module>/<controller>/index',
    'POST <module>/<controller>' => '<module>/<controller>/create',
    'GET,HEAD <module>/<controller>/<id:\d+>' => '<module>/<controller>/view',
    'PUT,PATCH <module>/<controller>/<id:\d+>' => '<module>/<controller>/update',
    'DELETE <module>/<controller>/<id:\d+>' => '<module>/<controller>/delete',
    'OPTIONS <module>/<controller>' => '<module>/<controller>/options',
    'OPTIONS <module>/<controller>/<wildcard:.*>' => '<module>/<controller>/options',
    '<module>/<controller>/<action>' => '<module>/<controller>/<action>',
];
