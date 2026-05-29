<?php

namespace santilin\churros\models;

use yii\db\ActiveQuery;

trait ModelTrait
{
	/**
	 * Applies one or more scopes to an ActiveQuery object, adding a default order if none set.
	 *
	 * @param ActiveQuery $q The ActiveQuery object to apply scopes to.
	 * @param string|array $scopes A single scope (as a string) or an array of scopes to apply.
	 *                             Each scope can be a string (function name) or an array (function name with arguments).
	 * @return ActiveQuery The modified ActiveQuery object with applied scopes.
	 *
	 */
	static public function applyScopes(ActiveQuery $q, string|array|null $scopes, bool $set_order_by = true): ActiveQuery
	{
		if (!empty($scopes)) {
			$all_scopes = [];
			foreach ((array) $scopes as $scope) {
				if (is_string($scope)) {
					if ($scope[0] === '[') {
						$inner_scopes = json_decode($scope);
						foreach ($inner_scopes as $inner_scope) {
							$all_scopes = array_merge($all_scopes, explode(',', $inner_scope));
						}
					} else {
						$all_scopes = array_merge($all_scopes, explode(',', $scope));
					}
				} else {
					$all_scopes[] = $scope;
				}
			}
			$save_order = $q->orderBy;
			foreach ($all_scopes as $scope) {
				$scope_args = [];
				if (is_array($scope)) {
					$scope_func = trim(array_shift($scope));
					$scope_args = $scope;
				} elseif (str_contains($scope, '(')) {
					$scope = trim($scope);
					// Handle scope like "porPuesto(9)" or "porPuesto('text')"
					$paren_pos = strpos($scope, '(');
					$scope_func = substr($scope, 0, $paren_pos);
					$args_str = substr($scope, $paren_pos + 1, -1);
					if ($args_str !== '') {
						// Split by comma and trim quotes if present
						$raw_args = explode(',', $args_str);
						foreach ($raw_args as $arg) {
							$arg = trim($arg);
							if ($arg[0] === "'" && $arg[strlen($arg) - 1] === "'") {
								$scope_args[] = substr($arg, 1, -1);
							} elseif ($arg[0] === '"' && $arg[strlen($arg) - 1] === '"') {
								$scope_args[] = substr($arg, 1, -1);
							} else {
								$scope_args[] = (int)$arg;
							}
						}
					}
				} else {
					$scope_func = $scope;
				}
				if ($scope_func) {
					call_user_func_array([$q, $scope_func], $scope_args);
				}
			}
			if ($set_order_by && empty($save_order) && $q->orderBy == $save_order) {
				$q->defaultOrder();
			}
		} else if ($set_order_by) {
			if (method_exists($q, 'defaultOrder')) {
				$q->defaultOrder();
			}
		}
		return $q;
	}

	public function scenarios()
	{
		$scenarios = parent::scenarios();
		if (!array_key_exists($this->scenario, $scenarios)) {
			if (count($scenarios) == 1 && array_key_exists('default', $scenarios)) {
				$scenarios[$this->scenario] = $scenarios['default'];
			}
		}
		return $scenarios;
	}

	/**
	 * Get all attributes that start with a given prefix.
	 *
	 * @param string $prefix The prefix to filter properties by.
	 * @return array An array of property names that start with the specified prefix.
	 */
	public function attributesWithPrefix(string $prefix, bool $remove_prefix = false)
	{
		// Get all class properties
		$classProperties = get_object_vars($this);

		// Filter properties by prefix
		$filteredProperties = [];
		$lp = strlen($prefix);
		foreach ($classProperties as $kp => $p) {
			if (strpos($kp, $prefix) === 0) {
				if ($remove_prefix) {
					$kp = substr($kp, $lp);
				}
				$filteredProperties[$kp] = $p;
			}
		}

		return $filteredProperties;
	}

	/**
	 * Get all attributes that start with a given prefix.
	 *
	 * @param string $prefix The prefix to filter properties by.
	 * @return array An array of property names that start with the specified prefix.
	 */
	public function attributesWithoutPrefix($prefix)
	{
		// Get all class properties
		$at= get_object_vars($this);

		// Filter properties by prefix
		$filteredProperties = [];
		foreach ($this->getAttributes() as $kp => $p) {
			if (strpos($kp, $prefix) !== 0) {
				$filteredProperties[$kp] = $p;
			}
		}
		return $filteredProperties;
	}


} // trait
