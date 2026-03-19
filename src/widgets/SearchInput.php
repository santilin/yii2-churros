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
	public bool $multiple = false;

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

		if ($this->type == 'dropdown') {
			$ret .= Html::hiddenInput("{$scope}[$attribute][op]", $value['op']);
			if (isset($value['v'])) {
				if (is_array($value['v'])) {
					foreach ($value['v'] as $k => $v) {
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
			$inputName = "{$scope}[$attribute][v]" . ($this->multiple ? '[]' : '');
			if ($this->multiple) {
				$options = ArrayHelper::merge($this->options, [
					'multiple' => true,
					'class' => 'form-select select2-search-dropdown',
				]);
				echo \kartik\select2\Select2::widget([
					'name' => $inputName,
					'value' => $value['v'],
					'data' => $this->dropDownValues,
					'options' => $options,
					'pluginOptions' => [
						'placeholder' => $this->options['prompt'] ?? 'Cualquiera',
						'allowClear' => true,
					],
					'language' => substr(Yii::$app->language, 0, 2),
					'bsVersion' => '5',
				]);
			} else {
				Html::removeCssClass($this->options, 'form-control');
				if (!isset($this->options['prompt'])) {
					$this->options['prompt'] = 'Cualquiera';
				}
				Html::addCssClass($this->options, 'form-select');
				Html::addCssStyle($this->options, [ 'width' => 'fit-content' ]);
				$ret .= Html::dropDownList($inputName,
					$value['v'], $this->dropDownValues, $this->options);
			}
		} else {
			$ret .= Html::dropDownList("{$scope}[$attribute][op]",
				$value['op'], FormHelper::$operators, [
				'id' => "drop-op-$attr_class", 'class' => 'form-select search-dropdown w-auto',
				'prompt' => 'Operador']);
			Html::addCssClass($this->options, 'd-flex form-control');
			Html::addCssStyle($this->options, [ 'width' => 'fit-content' ]);
			$ret .= Html::input($control_type, "{$scope}[$attribute][v]", $value['v'], $this->options);
		}
		return $ret;
	}

}
