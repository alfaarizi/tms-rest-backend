<?php

namespace app\exceptions;

class CodeCheckerResultNotifierException extends \Exception
{
    public const CONFIG = 0;
    public const EMAIL = 1;
    public const CANVAS = 2;
}
