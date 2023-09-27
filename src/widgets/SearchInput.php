<?php
namespace santilin\churros\widgets;

use Yii;
use yii\helpers\{ArrayHelper,Html};
use santilin\churros\ModelSearchTrait;

class SearchInput extends \yii\bootstrap4\InputWidget
{
	public $type = 'string';
	public $searchOptions = [];

	public function run()
	{
		$searchOptions = $this->searchOptions;
		if( array_key_exists( 'values', $searchOptions ) ) {
			$dropdown_values = $searchOptions['values'];
		} else {
			$dropdown_values = null;
		}
		$attribute = $this->attribute;
		$attr_class = str_replace('.','_',$attribute);
		switch( $this->type ) {
		default:
			$control_type = 'text';
		}
		$ret = '';
		$scope = $searchOptions['scope']??$this->model->formName();
		// $this->model is a ModelSearchTrait
		$value = $this->model->$attribute;
		$value = ModelSearchTrait::toOpExpression($value, false);
		$ret .= "<div class='row form-inline'>";
		$ret .= "<div class='operators control-form'>";
		$ret .= Html::dropDownList("${scope}[$attribute][op]",
			$value['op'], FormHelper::$operators, [
			'id' => "drop-op-$attr_class", 'class' => 'search-dropdown',
			'Prompt' => 'Operador']);
		$ret .= "</div>";

		if( is_array($dropdown_values) || is_array($value['v']) ) {
			$ret .= "<div class='left-field control-form'>";
			$ret .= Html::dropDownList("${scope}[$attribute][v]",
				$value['v'], $dropdown_values,
				array_merge($searchOptions['htmlOptions']??[], [ 'prompt' => Yii::t('churros', 'Cualquiera')]));
			$ret .= "</div>";
		} else {
			$ret .= <<<EOF
	<div class="input-group col-sm-5">
EOF;
			$ret .= Html::input($control_type, "${scope}[$attribute][v]", $value['v'],
				array_merge($searchOptions['htmlOptions']??[], [ 'class' => 'form-control' ]));
			$ret .= <<<EOF
    </div>
EOF;
		}
		$ret .= "</div><!-- row -->";
		return $ret;
	}

}
