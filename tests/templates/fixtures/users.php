<?php

/**
 * @var \Faker\Generator $faker
 * @var integer $index
 */
return [
    'userCode' => strtoupper(Yii::$app->getSecurity()->generateRandomString(6)),
    'name' => $faker->name,
    'email' => $faker->email,
];
