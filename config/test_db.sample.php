<?php

$db = require(__DIR__ . '/db.php');
// test database! Important not to run tests on production or development databases
$db['dsn'] = 'mysql:host=<db host neve>;dbname=<db neve>';

return $db;
