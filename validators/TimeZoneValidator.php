<?php

namespace app\validators;

use Yii;
use yii\validators\Validator;

/**
 * Validates if the given string is a valid timezone name
 */
class TimeZoneValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        if (!in_array($model->$attribute, timezone_identifiers_list())) {
            $this->addError($model, $attribute, Yii::t('app', 'Invalid timezone'));
        }
    }
}
