<?php
namespace santilin\churros\widgets;

use Yii;
use yii\helpers\{ArrayHelper,Html};
use santilin\churros\ModelInfoTrait;
use santilin\churros\helpers\FormHelper;

class SearchInput extends \yii\bootstrap5\InputWidget
{
 	public string $type = 'string';
	public ?string $formName;
	public array $dropDownValues = [];

	public function run()
	{
		$attribute = $this->attribute;
		$attr_class = str_replace('.','_',$attribute);
		switch( $this->type) {
		default:
			$control_type = 'text';
		}
		$ret = '';
		$scope = $this->formName??$this->model->formName();
		// $this->model is a ModelSearchTrait
		$value = $this->model->$attribute;
		$value = FormHelper::toOpExpression($value, false, $this->model->operatorForAttr(null, $attribute));
// 		$ret .= "<div class='row form-inline'>";
// 		$ret .= "<div class='left-field control-form'>";

		if ($this->type == 'dropdown') {
			Html::removeCssClass($this->options, 'form-control');
			Html::addCssClass($this->options, 'form-select');
			Html::addCssStyle($this->options, [ 'width' => 'fit-content' ]);
			$ret .= Html::hiddenInput("{$scope}[$attribute][op]", $value['op']);
			if (isset($value['v'])) {
				if (is_array($value['v'])) {
					foreach ($value['v'] as $k => $v) { // @todo se puede saber si es '' o no
						if ($v && $v[0] == "'") {
							$value['v'][$k] = intval(substr($v,1,-1));;
						}
					}
				} else {
					$value['v'] = (array)$value['v'];
				}
			} else {
				$value['v'] = null;
			}
			$ret .= Html::dropDownList("{$scope}[$attribute][v]",
				$value['v'], $this->dropDownValues, $this->options);
		} else {
			$ret .= Html::dropDownList("{$scope}[$attribute][op]",
				$value['op'], FormHelper::$operators, [
				'id' => "drop-op-$attr_class", 'class' => 'form-select search-dropdown w-auto',
				'Prompt' => 'Operador']);
			Html::addCssClass($this->options, 'form-control');
			Html::addCssStyle($this->options, [ 'width' => 'fit-content' ]);
			$ret .= Html::input($control_type, "{$scope}[$attribute][v]", $value['v'], $this->options);
		}
		return $ret;
	}

}
