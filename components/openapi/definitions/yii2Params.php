<?php

/**
 * @OA\Parameter(
 *      parameter="yii2_fields",
 *      name="fields",
 *      description="Yii2 select fields",
 *      @OA\Schema(
 *          type="string",
 *      ),
 *      in="query",
 *      required=false
 * )
 *
 * @OA\Parameter(
 *       parameter="yii2_expand",
 *       name="expand",
 *       description="Yii2 expand extra fields",
 *       @OA\Schema(
 *           type="string",
 *       ),
 *       in="query",
 *       required=false
 * )
 *
 *  @OA\Parameter(
 *       parameter="yii2_sort",
 *       name="sort",
 *       description="Yii2 sort collection by the given fields",
 *       @OA\Schema(
 *           type="string",
 *       ),
 *       in="query",
 *       required=false
 * )
 */
