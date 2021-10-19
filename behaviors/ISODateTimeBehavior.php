<?php
namespace app\behaviors;

use DateTime;
use DateTimeZone;
use Yii;
use yii\db\ActiveRecord;
use yii\base\Behavior;

/**
 * Converts the given datetime fields to ISO 8601 format after find and converts them back to SQL format before save
 */
class ISODateTimeBehavior extends Behavior
{
    public $attributes = [];

    public function events()
    {
        return [
            // Convert datetime to ISO format after find
            ActiveRecord::EVENT_AFTER_FIND => 'convertToISO',
            // Convert datetime to SQL format before save
            ActiveRecord::EVENT_BEFORE_INSERT => 'convertToSQL',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'convertToSQL',
            // Convert back to ISO format after save
            ActiveRecord::EVENT_AFTER_INSERT => 'convertToISO',
            ActiveRecord::EVENT_AFTER_UPDATE => 'convertToISO',
        ];
    }

    /**
     * Converts the given datetime fields to ISO 8601 datetime format
     */
    public function convertToISO($event)
    {
        foreach ($this->attributes as $attribute) {
            if ($this->owner->$attribute) {
                $datetime = new DateTime($this->owner->$attribute);
                $this->owner->$attribute = $datetime->format(DateTime::ATOM);
            }
        }
    }

    /**
     * Converts the given datetime fields to SQL datetime format
     */
    public function convertToSQL()
    {
        foreach ($this->attributes as $attribute) {
            if ($this->owner->$attribute) {
                $datetime = new DateTime($this->owner->$attribute);
                $datetime->setTimezone(new DateTimeZone(Yii::$app->timeZone));
                $this->owner->$attribute = $datetime->format('Y-m-d H:i:s');
            }
        }
    }
}
