<?php
namespace santilin\churros\yii;

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
		$scope = $this->model->formName();
		if( $this->model->hasAttribute($attribute) ) {
			$value = $this->model->$attribute;
		} else {
			$value = null;
		}
		$value = ModelSearchTrait::toOpExpression($value, false);
		if( !in_array($value['op'], ModelSearchTrait::$extra_operators) ) {
			$extra_visible = "display:none";
		} else {
			$extra_visible = '';
		}
		$ret .= "<div class='row form-inline'>";
		$ret .= "<div class='control-form'>";
		$ret .= Html::dropDownList("${scope}[$attribute][op]",
			$value['op'], ModelSearchTrait::$operators, [
			'id' => "drop-op-$attr_class", 'class' => 'search-dropdown',
			'Prompt' => 'Operador']);
		$ret .= "</div>";

		if( is_array($dropdown_values) || is_array($value['lft']) ) {
			$ret .= "<div class='control-form'>";
			$ret .= Html::dropDownList("${scope}[$attribute][lft]",
				$value['lft'], $dropdown_values,
				array_merge($searchOptions['htmlOptions']??[], [ 'prompt' => Yii::t('churros', 'Cualquiera')]));
			$ret .= "</div>";
		} else {
			$ret .= <<<EOF
	<div class="input-group col-sm-5">
EOF;

			$ret .= Html::input($control_type, "${scope}[$attribute][lft]", $value['lft'],
				array_merge($searchOptions['htmlOptions']??[], [ 'class' => 'form-control' ]));
			$ret .= <<<EOF
    </div>
EOF;
		}
		$ret .= "</div><!-- row -->";

		$ret .= <<<EOF
	<div style="$extra_visible" id="second-field-drop-$attr_class">
<div class="row gap10">
		<div class='control-label col-sm-2'></div>
		<div class='control-form col-sm-2 text-right'>
y:
</div>
EOF;

		if( is_array($dropdown_values) ) {
			$ret .= "<div class='control-form col-sm-5'>";
			$ret .= Html::dropDownList("${scope}[$attribute][rgt]",
				$value['rgt'], $dropdown_values,
				array_merge($searchOptions['htmlOptions']??[], [ 'prompt' => Yii::t('churros', 'Cualquiera')]));
		} else {
			$ret .= '<div class="input-group col-sm-5">';
			$ret .= Html::input($control_type, "${scope}[$attribute][rgt]", $value['rgt'],
				array_merge($searchOptions['htmlOptions']??[], [ 'class' => 'form-control' ]));
			$ret .= <<<EOF
EOF;
		}
		$ret .= "</div>";
		$ret .= "</div><!-- row -->";
		$ret .= "</div>";

// 		$ret .= "</div>";
		return $ret;
	}

}
