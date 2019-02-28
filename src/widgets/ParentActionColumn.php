<?php

namespace santilin\Churros\widgets;

use yii\helpers\Url;
use yii\grid\ActionColumn;
use santilin\Churros\helpers\AppHelper;

class ParentActionColumn extends ActionColumn
{
	public $parent;

    public function createUrl($action, $model, $key, $index)
    {
        if (is_callable($this->urlCreator)) {
            return call_user_func($this->urlCreator, $action, $model, $key, $index, $this);
        }

        $params = is_array($key) ? $key : ['id' => (string) $key];
        $params[0] = $this->controller ? $this->controller . '/' . $action : $action;
// 		$params['parent'] = AppHelper::stripNamespaceFromClassName($this->parent);
// 		$params['parent_id'] = $this->parent->getPrimaryKey();
        return $this->prependParentRoute(Url::toRoute($params));
    }

	private function prependParentRoute($model_route)
	{
		if( $this->parent) {
			$prefix = $this->parent->getPrimaryKey();
			if( is_array($model_route) ) {
				$model_route[0] = $prefix . $model_route[0];
			} else {
				$model_route = $prefix . $model_route;
			}
		}
		return $model_route;
	}
}
