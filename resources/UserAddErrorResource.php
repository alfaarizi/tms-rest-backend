<?php

namespace app\resources;

use app\models\Model;

/**
 * Stores a neptun code, error pair
 */
class UserAddErrorResource extends Model
{
    public $neptun;
    public $cause;

    /**
     * UserAddErrorResource constructor.
     * @param $neptun
     * @param $cause
     */
    public function __construct($neptun, $cause)
    {
        parent::__construct();
        $this->neptun = $neptun;
        $this->cause = $cause;
    }
}
