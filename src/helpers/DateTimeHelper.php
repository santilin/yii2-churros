<?php

namespace santilin\Churros\helpers;

use \DateTime;

class DateTimeHelper
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
    public static function getTimeStampAgoInWord($timestamp)
    {
        $difference = time() - $timestamp;
        // Few seconds ago
        if ($difference < 15) {
            return \Yii::t('churros', 'Hace unos segundos');
        } // Seconds ago
        else if ($difference < 60) {
            return \Yii::t('churros', '{0, plural, =1{hace un segundo} other{hace # segundos}}', $difference);
        } // Minutes ago
        else if ($difference < 60 * 60) {
            $minutes = round($difference / 60);
            return \Yii::t('churros', '{0, plural, =1{hace un minuto} other{hace # minutos}}', $minutes);
        } // Hours ago
        else if ($difference < 24 * 60 * 60) {
            $hours = round($difference / 60 / 60);
            return \Yii::t('churros', '{0, plural, =1{hace una hora} other{hace # horas}}', $hours);
        } // Days ago
        else if ($difference < 7 * 24 * 60 * 60) {
            $days = round($difference / 24 / 60 / 60);
            return \Yii::t('churros', '{0, plural, =1{hace 1 día} other{hace # días}}', $days);
        } // Weeks ago
        else if ($timestamp > strtotime('-1 month')) {
            $weeks = round($difference / 7 / 24 / 60 / 60);
            return \Yii::t('churros', '{0, plural, =1{hace una semana} other{hace # semanas}}', $weeks);
        } // Months ago
        else if ($timestamp > strtotime('-1 year')) {
            $interval = date_diff((new DateTime(static::getTime($timestamp))), (new DateTime()));
            return \Yii::t('churros', '{0, plural, =1{hace un mes} other{hace # meses}}', $interval->m);
        }
        // Years ago
        $interval = date_diff((new DateTime(static::getTime($timestamp))), (new DateTime()));
        return \Yii::t('churros', '{0, plural, =1{hace un año} other{hace # años}}', $interval->y);
    }

    static public function dateToModelAttribute($date = null)
    {
		if ($date == null) {
			return strftime( self::STRFTIME_DATE_SQL_FORMAT, time() );
		} else {
			/// @todo
			return strftime( self::STRFTIME_DATE_SQL_FORMAT, self::anyDateToUnixTime($date) );
		}
    }

    static public function ahora()
    {
		return static::dateTimeToModelAttribute();
    }

	static public function dateTimeToModelAttribute($date = null)
    {
		if ($date == null) {
			return strftime( self::STRFTIME_DATETIME_SQL_FORMAT, time() );
		} else {
			return strftime( self::STRFTIME_DATETIME_SQL_FORMAT, self::anyDateTimeToUnixTime($date) );
		}
    }

    static public function anyDateTimeToUnixTime($datetime, $dateFormat = self::DATETIME_DATETIME_SQL_FORMAT)
    {
		if( $datetime instanceof DateTime ) {
			return $datetime->getTimestamp();
		} elseif( is_int($datetime) ) {
			return $datetime;
		} else {
			$sdate = trim((string)$datetime);
			$datetime = DateTime::createFromFormat($dateFormat, $sdate);
			if ($datetime === false ) {
				$variations = [
					'^([0-9]{4})/[0-9]{2}/[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{4}2$' => 'Y/m/d H:i:s',
					/// @todo more here
				];
				foreach ($variations as $regexp => $dateFormat) {
					if (preg_match ('|' . $regexp . '|', $sdate)) {
						break;
					}
				}
				if ($dateFormat == null) {
					throw new \Exception("No se reconoce el formato de fecha de " + $sdate );
				}
				$datetime = DateTime::createFromFormat($dateFormat, $sdate);
			}
			return $datetime->getTimestamp();
		}
	}

	public static function datetimeDiffToString($fecha_ini, $fecha_fin)
	{
		$ds = new DateTime($fecha_ini);
		$de = new DateTime($fecha_fin);
		$diff = $de->diff($ds);
		return $diff->format("%H:%I:%S");
	}

	function addMonths(DateTime $date, $months)
	{
		// We extract the day of the month as $start_day
		$start_day = $date->format('j');
		// We add months to the given date
		if( $months>0) {
			$date->modify("+{$months} month");
		} else {
			$date->modify("{$months} month");
		}
		// We extract the day of the month again so we can compare
		$end_day = $date->format('j');
		if ($start_day != $end_day)
		{
			// The day of the month isn't the same anymore, so we correct the date
			$date->modify('last day of last month');
		}
		return $date;
	}

}
