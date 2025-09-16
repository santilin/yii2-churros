<?php
namespace santilin\churros;

use yii;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\db\Query;
use yii\data\ActiveDataProvider;

trait ParamsModelTrait
{
	static public function getValue($var, $default = null)
	{
		$model = new static();
		$r = $model->find()
			->where([ $model->_param_name_field=> $var])
			->one();
		if ($r) {
			return trim($r->{$model->_param_value_field});
		} else {
			return $default;
		}
	}

} // trait

