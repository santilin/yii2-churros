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
			if (is_string($scopes)) {
				$scopes = explode(',', $scopes);
			}
			$save_order = $q->orderBy;
			foreach ( (array)$scopes as $scope) {
				$scope_args = [];
				if (is_array($scope)) {
					$scope_func = trim(reset($scope));
					$scope_args = $scope;
				} else {
					$scope_func = trim($scope);
				}
				if( $scope_func ) {
					call_user_func_array([$q,$scope_func],$scope_args);
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

} // trait
