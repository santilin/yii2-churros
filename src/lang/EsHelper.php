<?php

namespace santilin\churros\lang;

class EsHelper
{
	const SPANISH_MALE_WORDS = [
		"a" => "o",
		"as" => "os",
		"la" => "el",
		"La" => "El",
		"las" => "los",
		"Las" => "Los",
		"una" => "un",
		"un_a" => "uno",
		"Una" => "Un",
		"Un_a" => "Uno",
		"esta" => "este",
		"Esta" => "Este",
		"estas" => "estos",
		"Estas" => "Estos",
		"otra" => "otro",
		"Otra" => "Otra",
		"otras" => "otras",
		"Otras" => "Otras",
	];

	static public function strToTime($fecha_expr, bool &$has_time): string
	{
		// Dictionary of Spanish to English translations
		$translations = [
			'medianoche' => 'midnight',
			'mediodía' => 'noon',
			'enero' => 'January',
			'febrero' => 'February',
			'marzo' => 'March',
			'abril' => 'April',
			'mayo' => 'May',
			'junio' => 'June',
			'julio' => 'July',
			'agosto' => 'August',
			'septiembre' => 'September',
			'octubre' => 'October',
			'noviembre' => 'November',
			'diciembre' => 'December',
			'lunes' => 'Monday',
			'martes' => 'Tuesday',
			'miércoles' => 'Wednesday',
			'jueves' => 'Thursday',
			'viernes' => 'Friday',
			'sábado' => 'Saturday',
			'domingo' => 'Sunday',
			'mañana' => 'tomorrow',
			'próximo' => 'next',
			'último' => 'last',
			'semana' => 'week',
			'minuto' => 'minute',
			'segundo' => 'second',
			'hoy' => 'today',
			'ayer' => 'yesterday',
			'mes' => 'month',
			'año' => 'year',
			'día' => 'day',
			'hora' => 'hour',
			'este' => 'this',
		];
		if ( strpos($fecha_expr, 'este') === FALSE
			|| strpos($fecha_expr, 'mañana') === FALSE
			) {
			$translations['a las'] = '';
		} else {
			$translations['a las'] = '';
		}

		// Convert Spanish expression to lowercase for case-insensitive matching
		$fecha_expr = mb_strtolower($fecha_expr, 'UTF-8');

		// Translate Spanish words to English
		$english_expr = strtr($fecha_expr, $translations);

		// Parse the translated date expression
		$timestamp = strtotime($english_expr);

		if ($timestamp === false) {
			throw new \Exception("Unable to parse date: $fecha_expr: $english_expr");
		}
		$has_time = preg_match('/\d{1,2}[:h]\d{2}/', $english_expr) ||
			strpos($english_expr, 'noon') !== false ||
			strpos($english_expr, 'midnight') !== false;

		return $timestamp;
	}
}
