<?php

namespace app\components\openapi\definitions;

/**
 * @OA\Schema(
 *     schema="Yii2Error",
 *
 *     @OA\Property(type="string",property="name"),
 *     @OA\Property(type="string",property="message"),
 *     @OA\Property(type="integer",property="status"),
 *     @OA\Property(type="integer",property="code"),
 *     @OA\Property(type="string", property="type"),
 * )
 */
abstract class Yii2Error
{
}
