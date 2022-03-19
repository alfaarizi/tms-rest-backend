<?php

namespace app\components\openapi\exceptions;

use Throwable;
use yii\base\Exception;

class CodeGeneratorException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
