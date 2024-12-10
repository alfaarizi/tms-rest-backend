<?php

namespace app\validators;

use app\models\Group;
use Yii;
use yii\validators\Validator;

/**
 * Validates if the given sync levels are in an array and are valid sync levels
 */
class CanvasSyncLevelValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        $syncLevel = $model->$attribute;

        if (empty($syncLevel)) {
            return;
        }

        if (!is_array($syncLevel)) {
            $this->addError($model, $attribute, Yii::t('app', 'Sync level must be an array'));
            return;
        }

        $invalidValues = array_diff($syncLevel, Group::SYNC_LEVEL_VALUES);
        if (!empty($invalidValues)) {
            $this->addError($model, $attribute, Yii::t('app', 'Invalid sync level values: {values}', [
                'values' => implode(', ', $invalidValues)
            ]));
        }

        if (!in_array(Group::SYNC_LEVEL_NAME_LISTS, $syncLevel)) {
            $this->addError($model, $attribute, Yii::t('app', 'Name lists must be synchronized'));
        }
    }
}
