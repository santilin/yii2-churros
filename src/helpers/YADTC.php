<?php

namespace santilin\churros\helpers;


class YADTC extends \DateTime
{
	const SQL_DATE_FORMAT = 'Y-m-d';
	const NOW = '\\app\\lib\\YADTC::NOW';

    static public function createFromFormat($format, $time, \DateTimeZone $timezone = null)
    {
        $ext_dt = new static();
        $parent_dt = parent::createFromFormat($format, $time, $timezone);

        if (!$parent_dt) {
            return false;
        }

        $ext_dt->setTimestamp($parent_dt->getTimestamp());
        return $ext_dt;
    }

	/**
	 * Intenta crear un DateTime a partir de cualquier tipo de variable y sin tener que especificar
	 * el formato.
	 * Si la fecha es incorrecta o ambigua, lanza una excepción.
	 */
	static public function fromString($datetime, $format = null)
	{
		if( $datetime === null || $datetime instanceof \DateTime || $datetime instanceof YADTC ) {
			return $datetime;
		}
		if( is_object($datetime) && isset($datetime->date) && isset($datetime->timezone) ) {
			$datetime = new YADTC($datetime->date, new \DateTimeZone($datetime->timezone));
			return $datetime;
		}
		if( is_string($datetime) && $datetime == '' ) {
			return null;
		}
		if( is_int($datetime) ) {
			return new YADTC('@$datetime');
		}
		if( $format !== null ) {
			if( is_string($datetime) ) {
				$dt = self::createFromFormat($format, $datetime);
				if( $dt !== false ) {
					return $dt;
				}
			}
		}
		$sdate = trim((string)$datetime);
		$variations = [
			'^([0-9]{4})/[0-9]{2}/[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$' => 'Y/m/d H:i:s',
			'^([0-9]{4})-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$' => 'Y-m-d H:i:s',
			'^([0-9]{1,2})/[0-9]{1,2}/[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$' => '!d/m/y H:i:s', // form
			'^([0-9]{4})/[0-9]{2}/[0-9]{2} [0-9]{2}:[0-9]{2}$' => 'Y/m/d H:i',
			'^([0-9]{4})-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}$' => 'Y-m-d H:i',
			'^([0-9]{1,2})/[0-9]{1,2}/[0-9]{2} [0-9]{2}:[0-9]{2}$' => '!d/m/y H:i', // 			'^([0-9]{4})/[0-9]{2}/[0-9]{2}$' => '!Y/m/d', // tests
			'^([0-9]{4})-[0-9]{2}-[0-9]{2}$' => '!Y-m-d', // tests
			'^([0-9]{4})/[0-9]{2}/[0-9]{2}$' => '!Y/m/d', // tests
			'^([0-9]{1,2})/[0-9]{2}/[0-9]{4}$' => '!d/m/Y', // tests
			'^([0-9]{1,2})/[0-9]{1,2}/[0-9]{2}$' => '!d/m/y', // form
			'^([0-9]{1,2})-[0-9]{2}-[0-9]{4}$' => '!d-m-Y', // tests
			'^([0-9]{1,2})-[0-9]{1,2}-[0-9]{2}$' => '!d-m-y', // form
			'^[0-9]{2}:[0-9]{2}:[0-9]{2}$' => 'H:i:s',
			'^[0-9]{2}:[0-9]{2}$' => 'H:i',
		];
		foreach ($variations as $regexp => $format) {
			if (preg_match ('|' . $regexp . '|', $sdate)) {
				$datetime = self::createFromFormat($format, $sdate);
				if ($datetime !== false ) {
					return $datetime;
				}
			}
		}
 		throw new \Exception("No se reconoce el formato de la fecha '" . strval($sdate) . "'");
	}

	/**
	 * Creates a YADTC from a SQL string.
	 * @param string|mixed $date
	 * @param bool $onlymysql Admit only msyql format
	 */
	static public function fromSQL($date, $onlymysql = false)
	{
		if( $date == null ) {
			return null;
		}
		$ret = self::createFromFormat('Y-m-d H:i:s', $date);
		if( $ret === false ) {
			$ret = self::createFromFormat('!Y-m-d', $date);
		}
		if ($ret!==false) {
			return $ret;
		} else if (!$onlymysql) {
			return self::fromString($date);
		} else {
			throw new \Exception("La fecha " . print_r($date, true) . " no tiene formato sql");
		}
	}

	/**
	 * Formatea una fecha desde SQL.
	 * Útil para cuando la fecha puede ser nula.
	 */
	static public function formatFromSQL($format, $date, $onlymysql = false)
	{
		$ret = self::fromSQL($date, $onlymysql);
		if( $ret ) {
			return $ret->format($format);
		} else {
			return '';
		}
	}

	static public function fromFormToSql($datestr, $format)
	{
		if( $datestr instanceof YADTC || $datestr instanceof \DateTime) {
			$date = $datestr;
		} else {
			$date = self::fromCepaimForm($datestr);
		}
		if( $date ) {
			return $date->format($format);
		} else {
			return '';
		}
	}

	static public function today($modify = null, DateTimeZone $timezone = NULL)
	{
		$dt = new \DateTime("now", $timezone);
		if( $modify != '' ) {
			$dt->modify($modify);
		}
		$ts = $dt->getTimestamp();
        $ext_dt = new static();
		$ext_dt->setTimestamp($ts);
		return $ext_dt;

	}
	public function year()
	{
		return intval($this->format('Y'));
	}
	public function month()
	{
		return intval($this->format('m'));
	}
	public function day()
	{
		return intval($this->format('d'));
	}
	public function hour()
	{
		return intval($this->format('H'));
	}
	public function minute()
	{
		return intval($this->format('i'));
	}
	public function second()
	{
		return intval($this->format('s'));
	}
	public function hasTime()
	{
		return $this->hour() != 0 || $this->minute() != 0 || $this->second() != 0;
	}
	public function setYear($year)
	{
		$this->setDate( $year, $this->month(), $this->day());
		return $this;
	}
	public function setMonth($month)
	{
		$this->setDate( $this->year(), $month, $this->day());
		return $this;
	}
	public function setDay($day)
	{
		$this->setDate( $this->year(), $this->month(), $day);
		return $this;
	}
	public function __toString()
	{
		return $this->format('Y-m-d');
	}
	public function lastDayOfMonth($month = null, $year = null)
	{
		if( $month == null ) {
			$month = $this->month();
		}
		if( $year == null ) {
			$year = $this->year();
		}
		return ($month== 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31));
	}
	public function eq($other)
	{
		if( !$other instanceof \DateTime && !$other instanceof YADTC) {
			$other = static::fromString($other);
		}
		return $this->getTimestamp() == $other->getTimestamp();
	}
	public function neq($other)
	{
		if( !$other instanceof \DateTime && !$other instanceof YADTC) {
			$other = static::fromString($other);
		}
		return $this->getTimestamp() != $other->getTimestamp();
	}
	public function lt($other)
	{
		if( !$other instanceof \DateTime && !$other instanceof YADTC) {
			$other = static::fromString($other);
		}
		return $this->getTimestamp() < $other->getTimestamp();
	}
	public function lte($other)
	{
		if( !$other instanceof \DateTime && !$other instanceof YADTC) {
			$other = static::fromString($other);
		}
		return $this->getTimestamp() <= $other->getTimestamp();
	}
	public function gt($other)
	{
		if( !$other instanceof \DateTime && !$other instanceof YADTC) {
			$other = static::fromString($other);
		}
		return $this->getTimestamp() > $other->getTimestamp();
	}
	public function gte($other)
	{
		if( !$other instanceof \DateTime && !$other instanceof YADTC) {
			$other = static::fromString($other);
		}
		return $this->getTimestamp() >= $other->getTimestamp();
	}
	public function betweenOnlyDates($d1, $d2)
	{
		$d = clone $this;
		$d->setTime(0,0);
		$cd1 = clone $d1;
		$cd1->setTime(0,0);
		$cd2 = clone $d2;
		$cd2->setTime(0,0);
		return $d->getTimestamp() >= $cd1->getTimeStamp()
			&& $d->getTimestamp() <= $cd2->getTimeStamp();
	}
	public function between($d1, $d2)
	{
		return $this->getTimestamp() >= $d1->getTimeStamp()
			&& $this->getTimestamp() <= $d2->getTimeStamp();
	}
	public function diff($other, $absolute = NULL)
	{
        if( $other instanceof YADTC ) {
            $t = $other->getTimeStamp();
            $other = new \DateTime();
            $other->setTimestamp($t);
		} else if( !$other instanceof \DateTime) {
			$other = \DateTime::fromString($other);
		}
		return parent::diff($other, $absolute);
	}
	public function formatCepaimForm()
	{
		return self::format('d/m/y');
	}

	public function formatSQLDate()
	{
		return self::format('Y-m-d');
	}
	public function formatSQLDateTime()
	{
		return self::format('Y-m-d H:i:s');
	}

	static public function onlyDateStr($date)
	{
        return substr(strval($date),0,10);
	}

	/**
	* Convert date/time format between `date()` and `strftime()`
	*
	* Timezone conversion is done for Unix. Windows users must exchange %z and %Z.
	*
	* Unsupported date formats : S, n, t, L, B, G, u, e, I, P, Z, c, r
	* Unsupported strftime formats : %U, %W, %C, %g, %r, %R, %T, %X, %c, %D, %F, %x
	*
	* @example Convert `%A, %B %e, %Y, %l:%M %P` to `l, F j, Y, g:i a`, and vice versa for "Saturday, March 10, 2001, 5:16 pm"
	* @link http://php.net/manual/en/function.strftime.php#96424
	*
	* @param string $format The format to parse.
	* @param string $syntax The format's syntax. Either 'strf' for `strtime()` or 'date' for `date()`.
	* @return bool|string Returns a string formatted according $syntax using the given $format or `false`.
	*/
	static private function date_format_to( $format, $syntax )
	{
		// http://php.net/manual/en/function.strftime.php
		$strf_syntax = [
			// Day - no strf eq : S (created one called %O)
			'%O', '%d', '%a', '%e', '%A', '%u', '%w', '%j',
			// Week - no date eq : %U, %W
			'%V',
			// Month - no strf eq : n, t
			'%B', '%m', '%b', '%-m',
			// Year - no strf eq : L; no date eq : %C, %g
			'%G', '%Y', '%y',
			// Time - no strf eq : B, G, u; no date eq : %r, %R, %T, %X
			'%P', '%p', '%l', '%I', '%H', '%M', '%S',
			// Timezone - no strf eq : e, I, P, Z
			'%z', '%Z',
			// Full Date / Time - no strf eq : c, r; no date eq : %c, %D, %F, %x
			'%s'
		];

		// http://php.net/manual/en/function.date.php
		$date_syntax = [
			'S', 'd', 'D', 'j', 'l', 'N', 'w', 'z',
			'W',
			'F', 'm', 'M', 'n',
			'o', 'Y', 'y',
			'a', 'A', 'g', 'h', 'H', 'i', 's',
			'O', 'T',
			'U'
		];

		switch ( $syntax ) {
			case 'date':
				$from = $strf_syntax;
				$to   = $date_syntax;
				break;

			case 'strf':
				$from = $date_syntax;
				$to   = $strf_syntax;
				break;

			default:
				return false;
		}

		$pattern = array_map(
			function ( $s ) {
				return '/(?<!\\\\|\%)' . $s . '/';
			},
			$from
		);

		return preg_replace( $pattern, $to, $format );
	}

	/**
	* Equivalent to `date_format_to( $format, 'date' )`
	*
	* @param string $strf_format A `strftime()` date/time format
	* @return string
	*/
	static public function strftime_format_to_date_format( $strf_format )
	{
		return self::date_format_to( $strf_format, 'date' );
	}

	/**
	* Equivalent to `convert_datetime_format_to( $format, 'strf' )`
	*
	* @param string $date_format A `date()` date/time format
	* @return string
	*/
	static public function date_format_to_strftime_format( $date_format )
	{
		return self::date_format_to( $date_format, 'strf' );
	}

	static public function edad(\DateTime $nacimiento, \DateTime $adiade = null )
	{
		if( $adiade == null ) {
			$adiade = new \DateTime("now", $nacimiento->getTimezone());
		}
		$age = $nacimiento->diff($adiade)->y;
		return $age;
	}

	public function calcNights(YADTC $other)
	{
		$this->resetTime();
		$other->resetTime();
		$diff = $this->diff($other);
		return $diff->d;
	}

    public function resetTime()
    {
        $this->setTime(0,0,0);
    }

    public function addDays($days)
    {
		if( $days < 0 ) {
			$this->modify("$days days");
		} else {
			$this->modify("+$days days");
		}
		return $this;
    }

	function addMonths($months)
	{
		// We extract the day of the month as $start_day
		$start_day = $date->format('j');
		// We add months to the given date
		if( $months>0) {
			$this->modify("+{$months} month");
		} else {
			$this->modify("{$months} month");
		}
		// We extract the day of the month again so we can compare
		$end_day = $date->format('j');
		if ($start_day != $end_day)
		{
			// The day of the month isn't the same anymore, so we correct the date
			$this->modify('last day of last month');
		}
		return $this;
	}

    /**
     * Convierte un string de tipo time a horas o minutos o segundos
     */
    static public function timeToFloat($time, $to = 'horas'){

        $hours = date('H', strtotime($time));
        $minutes = date('i', strtotime($time));
        $seconds = date('s', strtotime($time));

        switch($to){
            case 'horas':
                $float = $hours + ($minutes / 60) + ($seconds / 3600);
                return number_format($float, 4);
                break;
            case 'minutos':
                $float = ($hours * 60) + $minutes + ($seconds / 60);
                return number_format($float, 4);
                break;
            case 'segundos':
                return ($hours * 3600) + ($minutes * 60) + $seconds;
                break;
        }
    }
    /**
     * Convierte un float al formato time pasado como parámetro
     */
    static public function floatToTime($float, $format = 'H:i'){

        $float = number_format($float, 4);

        switch($format){
            case 'H:i:s':
                $hourFraction = $float - (int)$float;
                $minutes = $hourFraction * 60;
                $minutesFraction = $minutes - (int)$minutes;
                $seconds = $minutesFraction * 60;
                $hours = (int)$float;
                $minutes = (int)$minutes;
                $seconds = (int)$seconds;
                return str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad($seconds, 2, '0', STR_PAD_LEFT);
                break;
            case 'H:i':
                $hourFraction = $float - (int)$float;
                $minutes = round($hourFraction * 60);
                $minutesFraction = $minutes - (int)$minutes;
                $hours = (int)$float;
                $minutes = (int)$minutes;
                return str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT);
                break;
            case 'H':
                return $float;
                break;
        }
    }

	const FORMAT_SPANISH = 'j \d\e F \d\e Y';
	public function spanish($format = self::FORMAT_SPANISH)
	{
		$save_locale = setlocale(LC_TIME, 'es_ES');
 		$format = str_replace('\\', '', self::date_format_to_strftime_format($format));
 		$ret = strftime($format, $this->getTimeStamp());
 		$ret = strtr($ret, [
			'January' => 'enero',
			'February' => 'febrero',
			'March' => 'marzo',
			'April' => 'abril',
			'May' => 'mayo',
			'June' => 'junio',
			'July' => 'julio',
			'August' => 'agosto',
			'September' => 'septiembre',
			'October' => 'octubre',
			'November' => 'noviembre',
			'December' => 'diciembre'
		]);
		setlocale(LC_TIME, $save_locale);
		return $ret;
	}


	static public function selectMonths($nyears = 2, $year_ini = null)
	{
		static $months = ['enero','febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

		if( $year_ini == null ) {
			$year_ini=strftime('%Y',time());
		}
		$ret = [];
		for( $y=$year_ini; $y<$year_ini+$nyears; ++$y ) {
			$i=1;
			foreach ($months as $m) {
				$ret[$y.'-'.str_pad($i++,2,'0',STR_PAD_LEFT).'-01']=$m.' '.$y;
			}
		}
		return $ret;
	}

	static public function selectMonthsBetween($date_ini, $date_fin)
	{
		static $months = [ 1 => 'enero','febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

        $fecha_ini = self::createFromFormat('Y-m-d', $date_ini);
        $fecha_fin = self::createFromFormat('Y-m-d', $date_fin);

		$ret = [];
        $primer_anyo = $fecha_ini->year();
        $ultimo_anyo = $fecha_fin->year();

        for( $y = $fecha_ini->year(); $y <= $fecha_fin->year() ; ++$y ) {
			foreach ($months as $num_mes => $nombre_mes) {
                if($primer_anyo == $y){
                    if ($num_mes < $fecha_ini->month()) continue;
                }
                if($ultimo_anyo == $y){
                    if ($num_mes > $fecha_fin->month()) continue;
                }
                $ret[$y.'-'.str_pad($num_mes,2,'0',STR_PAD_LEFT).'-01'] = $nombre_mes.' '.$y;
			}
		}
		return $ret;
	}


} // class YADTC

