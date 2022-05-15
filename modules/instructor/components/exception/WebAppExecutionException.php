<?php

namespace app\modules\instructor\components\exception;

use Yii;

class WebAppExecutionException extends \Exception
{
    public static int $PREPARATION_FAILURE = 1;
    public static int $START_UP_FAILURE = 2;
    const SHUTDOWN_FAILURE = 3;

    public function __construct($message = "", $code = 0,  $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getMessageTranslated(): string
    {
        return Yii::t('app', $this->getMessage());
    }

}
