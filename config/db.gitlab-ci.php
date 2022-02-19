<?php

$db = require(__DIR__ . '/db.sample.php');
// test database! Important not to run tests on production or development databases
$db['dsn'] = 'mysql:host=mariadb;dbname=tms';
$db['username'] = 'tms';
$db['password'] = 'tms';

return $db;
