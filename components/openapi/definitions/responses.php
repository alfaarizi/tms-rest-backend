<?php

/**
 * @OA\Response(
 *     response=400,
 *     description="bad request",
 *     @OA\JsonContent(ref="#/components/schemas/Yii2Error"),
 * ),
 * @OA\Response(
 *     response=401,
 *     description="unauthorized: missing, invalid or expired access token",
 *     @OA\JsonContent(ref="#/components/schemas/Yii2Error"),
 * ),
 * @OA\Response(
 *     response=403,
 *     description="forbidden",
 *     @OA\JsonContent(ref="#/components/schemas/Yii2Error"),
 * ),
 * @OA\Response(
 *     response=404,
 *     description="object not found",
 *     @OA\JsonContent(ref="#/components/schemas/Yii2Error"),
 * ),
 * @OA\Response(
 *     response=409,
 *     description="conflict",
 *     @OA\JsonContent(ref="#/components/schemas/Yii2Error"),
 * ),
 *  @OA\Response(
 *     response=422,
 *     description="validation errors",
 *     @OA\JsonContent(type="object"),
 * ),
 * @OA\Response(
 *     response=500,
 *     description="internal server error",
 *     @OA\JsonContent(ref="#/components/schemas/Yii2Error"),
 * )
 */
