<?php

namespace app\exceptions;

class EvaluatorTarBuilderException extends \Exception
{
    public const ADD = 0;
    public const BUILD = 1;
    public const CLEANUP = 2;
}
