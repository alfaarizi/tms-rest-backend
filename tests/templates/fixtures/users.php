<?php

/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'userCode' => strtoupper(Yii::$app->getSecurity()->generateRandomString(6)),
    'name' => $faker->name, /** @phpstan-ignore-line */
    'email' => $faker->email, /** @phpstan-ignore-line */
];
