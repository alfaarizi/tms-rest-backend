<?php

namespace app\components;

use Exception;
use Yii;
use DateTime;
use DateTimeZone;

/**
 * Contains helper functions to work with datetime values and datetime strings
 */
class DateTimeHelpers
{
    /**
     * Converts the given datetime string to the given timezone
     * Then return a new datetime string in 'Y-m-d H:i:s' format
     * @param string $datetime Input datetime in the server's timezone
     * @param string $timezone Output timezone
     * @param bool $showTimezoneName True to include the timezone in the returned string.
     * @return string Output datetime in the given timezone
     * @throws Exception Emitted if the given datetime or timezone was not valid.
     */
    public static function timeZoneConvert($datetime, $timezone, $showTimezoneName)
    {
        $dateTime = new DateTime($datetime, new DateTimeZone(Yii::$app->timeZone));
        $dateTime->setTimezone(new DateTimeZone($timezone));
        $ret = $dateTime->format('Y-m-d H:i:s');
        if ($showTimezoneName) {
            $ret .= ' (' . $timezone . ')';
        }
        return $ret;
    }
}
