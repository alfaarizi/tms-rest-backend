<?php

namespace app\components\openapi\definitions;

/**
 * @OA\Info(
 *     title=OPEN_API_NAME,
 *     description=OPEN_API_DESCRIPTION,
 *     version=OPEN_API_VERSION,
 * )
 *
 * @OA\Server(
 *     description="Current server",
 *     url=OPEN_API_HOST
 * )
 */
abstract class ApiInfo
{
}
