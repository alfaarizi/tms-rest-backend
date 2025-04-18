<?php

namespace app\validators;

use Yii;
use yii\validators\Validator;

/**
 * Validates that both 'day' and 'startTime' are either set together or both are null.
 */
class DayAndStartTimeValidator extends Validator
{
    public $dayAttribute;
    public $startTimeAttribute;

    public function validateAttribute($model, $attribute)
    {
        $day = $model->{$this->dayAttribute};
        $startTime = $model->{$this->startTimeAttribute};

        if (($day === null && $startTime !== null) || ($day !== null && $startTime === null)) {
            $this->addError(
                $model,
                $attribute,
                Yii::t('app', 'Both Day and Start Time must be set together or both left empty.')
            );
        }
    }
}