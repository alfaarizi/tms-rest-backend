<?php

namespace app\exceptions;

class AddFailedException extends \yii\base\UserException
{
    protected $identifier;
    protected $cause;

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return mixed
     */
    public function getCause()
    {
        return $this->cause;
    }

    /**
     * AddFailedException constructor.
     * @param string $identifier;
     * @param mixed $cause
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($identifier, $cause, $code = 0, Throwable $previous = null)
    {
        parent::__construct("Failed to add item with $identifier", $code, $previous);
        $this->identifier = $identifier;
        $this->cause = $cause;
    }
}
