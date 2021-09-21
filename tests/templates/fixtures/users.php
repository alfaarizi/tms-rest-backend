<?php

/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'neptun' => strtoupper(Yii::$app->getSecurity()->generateRandomString(6)),
    'name' => $faker->name,
    'email' => $faker->email,
];
