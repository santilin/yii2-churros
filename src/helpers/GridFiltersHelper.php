<?php

namespace santilin\churros\helpers;

use Yii;

/**
 * Guarda, restaura y borra los filtros de los grids en la sesión del usuario.
 *
 * Funciona sólo si la app activa Yii::$app->params['churros.gridSaveFilters']
 * (opt-in), para no cambiar el comportamiento de las apps que ya funcionan.
 *
 * La persistencia se activa desde el botón "clip" de la fila de filtros del
 * grid (ActionColumn::renderFilterCell()), que manda los parámetros de query
 * _save_filters y _clear_filters; desde ese momento, y mientras el clip siga
 * activo, cada filtrado re-graba los filtros (autoguardado continuo).
 *
 * Estructura de sesión:
 *   $_SESSION['churros.grid-filters']['<ruta>|<_v>']['<NombreModelo_Search>'] = [filtro => valor]
 * La clave por ruta y vista (_v) hace que cada grid de la página guarde lo suyo
 * sin pisarse con el resto.
 */
class GridFiltersHelper
{
	public const SESSION_KEY = 'churros.grid-filters';
	/// Parámetros de query con los que el JS del grid pide guardar/borrar
	public const SAVE_PARAM = '_save_filters';
	public const CLEAR_PARAM = '_clear_filters';
	/// Clave de Yii::$app->params que activa la funcionalidad en la app
	public const PARAM_SWITCH = 'churros.gridSaveFilters';

	public static function isEnabled(): bool
	{
		return (bool) (Yii::$app->params[self::PARAM_SWITCH] ?? false);
	}

	/**
	 * Clave de sesión del grid: ruta actual + vista (_v), sin el formName.
	 * @return string
	 */
	public static function routeKey(): string
	{
		$route = Yii::$app->controller ? Yii::$app->controller->getRoute() : '';
		$v = Yii::$app->request
			? (string) (Yii::$app->request->queryParams[FormHelper::VIEWS_NVIEW_PARAM] ?? '0')
			: '0';
		return $route . '|' . $v;
	}

	/**
	 * @return array<string, array> filtros guardados [formName => filtros] para la ruta actual
	 */
	public static function savedForRoute(): array
	{
		return static::all()[static::routeKey()] ?? [];
	}

	public static function isSaved(string $formName): bool
	{
		return array_key_exists($formName, static::savedForRoute());
	}

	/**
	 * Se llama desde CrudReadOnlyController::beforeAction().
	 *
	 * Consume los flags de la petición, retira los de la URL (para que no se
	 * re-envíen en los siguientes filtrados ni cambien de sentido al recargar
	 * la página) y devuelve los query params listos para la acción: con la
	 * restauración de los filtros guardados, si procede.
	 *
	 * @param array $params query params actuales
	 * @return array los mismos params, sin los flags y con los filtros restaurados
	 */
	public static function handleRequest(array $params): array
	{
		if (!static::isEnabled()) {
			return $params;
		}
		$saveRequested = array_key_exists(self::SAVE_PARAM, $params);
		$clearRequested = array_key_exists(self::CLEAR_PARAM, $params);
		$save = !in_array((string) ($params[self::SAVE_PARAM] ?? ''), ['0', 'false', ''], true);
		// Los flags son instrucciones puntuales, no parte del estado de la URL
		unset($params[self::SAVE_PARAM], $params[self::CLEAR_PARAM]);
		static::stripFlagsFromGlobals();

		$routeKey = static::routeKey();
		$saved = static::all()[$routeKey] ?? [];

		// Formularios de búsqueda que traen filtros en esta petición
		$inRequest = [];
		foreach ($params as $formName => $value) {
			if (is_array($value) && str_ends_with((string) $formName, '_Search')) {
				$inRequest[] = (string) $formName;
			}
		}

		if (!$saveRequested && !$clearRequested && !$inRequest && !$saved) {
			return $params;
		}

		if ($clearRequested || ($saveRequested && !$save)) {
			// "Eliminar filtros" o 2ª pulsación del clip: se borra lo guardado.
			// Los filtros que trae la petición siguen valiendo para la vista actual.
			static::clearRoute($routeKey);
			return $params;
		}

		if ($saveRequested) {
			foreach ($inRequest as $formName) {
				static::save($routeKey, $formName, (array) $params[$formName]);
			}
			return $params;
		}

		// Autoguardado: sólo re-graba el formulario del grid que está en modo "guardar"
		foreach ($inRequest as $formName) {
			if (isset($saved[$formName])) {
				static::save($routeKey, $formName, (array) $params[$formName]);
			}
		}

		// Restauración: sólo si esta petición no trae filtros de ese formulario
		if (!$inRequest) {
			foreach ($saved as $formName => $filters) {
				if (!empty($filters)) {
					$params[$formName] = $filters;
				}
			}
		}
		return $params;
	}

	protected static function save(string $routeKey, string $formName, array $filters): void
	{
		// Sólo se guardan los filtros con valor: un grid sin filtros no tiene
		// nada que persistir y el clip se queda apagado
		$filters = array_filter($filters, static fn ($v) => $v !== '' && $v !== null && $v !== []);
		$data = static::all();
		if (empty($filters)) {
			unset($data[$routeKey][$formName]);
			static::pruneRoute($data, $routeKey);
			static::write($data);
			return;
		}
		$data[$routeKey][$formName] = $filters;
		static::write($data);
	}

	protected static function clearRoute(string $routeKey): void
	{
		$data = static::all();
		unset($data[$routeKey]);
		static::write($data);
	}

	/**
	 * Yii::$app->session es ArrayAccess: el valor sale por copia, así que no se
	 * puede escribir de forma indirecta ([...][...] = ...). Hay que leer,
	 * modificar y devolver el array entero.
	 * @return array<string, array<string, array>> [ruta => [formName => filtros]]
	 */
	protected static function all(): array
	{
		return Yii::$app->session[self::SESSION_KEY] ?? [];
	}

	protected static function write(array $data): void
	{
		if (empty($data)) {
			unset(Yii::$app->session[self::SESSION_KEY]);
			return;
		}
		Yii::$app->session[self::SESSION_KEY] = $data;
	}

	/// Deja la sesión sin rutas que ya no guardan nada
	protected static function pruneRoute(array &$data, string $routeKey): void
	{
		if (empty($data[$routeKey])) {
			unset($data[$routeKey]);
		}
	}

	/**
	 * Quita los flags de $_GET, QUERY_STRING y REQUEST_URI para que ni los
	 * enlaces generados (Url::current(), filterUrl del grid) ni una recarga
	 * de la página los arrastren.
	 */
	protected static function stripFlagsFromGlobals(): void
	{
		unset($_GET[self::SAVE_PARAM], $_GET[self::CLEAR_PARAM]);

		if (isset($_SERVER['QUERY_STRING'])) {
			$_SERVER['QUERY_STRING'] = implode('&', static::withoutFlags($_SERVER['QUERY_STRING']));
		}
		if (isset($_SERVER['REQUEST_URI'])) {
			$uri = $_SERVER['REQUEST_URI'];
			$qpos = strpos($uri, '?');
			if ($qpos !== false) {
				$query = implode('&', static::withoutFlags(substr($uri, $qpos + 1)));
				$_SERVER['REQUEST_URI'] = substr($uri, 0, $qpos) . ($query === '' ? '' : '?' . $query);
			}
		}
	}

	/**
	 * @return array de pares "clave=valor" de una query string, sin los flags
	 */
	protected static function withoutFlags(string $query): array
	{
		$pairs = [];
		foreach (explode('&', $query) as $pair) {
			if ($pair === '') {
				continue;
			}
			$key = urldecode((string) strstr($pair, '=', true));
			if (in_array($key, [self::SAVE_PARAM, self::CLEAR_PARAM], true)) {
				continue;
			}
			$pairs[] = $pair;
		}
		return $pairs;
	}
}
