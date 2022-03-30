<?php

namespace santilin\churros\helpers;

use \DateTime;
use yii;

class DateTimeEx extends YADTC
{
	const STRFTIME_DATETIME_SQL_FORMAT = '%Y-%m-%d %H:%M:%S';
	const DATETIME_DATETIME_SQL_FORMAT = 'Y-m-d H:i:s';
	const STRFTIME_DATE_SQL_FORMAT = '%Y-%m-%d';
	const DATETIME_DATE_SQL_FORMAT = 'Y-m-d';
	const STRFTIME_TIME_SQL_FORMAT = '%H:%M:%S';
	const DATETIME_TIME_SQL_FORMAT = 'H:i:s';

    public static function getTimeStringAgoInWord($date)
    {
		return static::getTimeStampAgoInWord(static::anyDateTimeToUnixTime($date));
    }

    /**
     * Returns time ago in words.
     * @param int $timestamp
     * @return string
     */
    public static function asDuration($timestamp)
    {

        $difference = time() - static::anyDateTimeToUnixTime($timestamp);
        $ret = Yii::$app->formatter->asDuration($difference, ",");
        $parts = explode(",", $ret);
        if( count($parts) >= 2 ) {
			return $parts[0] . " y " . $parts[1];
		} else {
			return $ret;
		}
    }

	public static function datetimeDiffToString($fecha_ini, $fecha_fin)
	{
		$ds = new DateTime($fecha_ini);
		$de = new DateTime($fecha_fin);
		$diff = $de->diff($ds);
		return $diff->format("%H:%I:%S");
	}

}
