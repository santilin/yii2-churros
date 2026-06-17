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
		$scope = $this->formName ?? $this->model->formName();
		// $this->model is a ModelSearchTrait
		$value = $this->model->$attribute;
		if (is_array($value) && isset($value['op']) && isset($value['v'])) {
			$inputValue = $value['v'];
		} else {
			$value = FormHelper::toOpExpression($value, false, $this->model->operatorForAttr(null, $attribute));
			$inputValue = $value['v'] ?? null;
		}

		if ($this->type === 'dropdown') {
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
			$currentOp = $value['op'] ?: '=';
			$currentIcon = FormHelper::$operatorIcons[$currentOp] ?? $currentOp;
			$currentLabel = FormHelper::$operators[$currentOp] ?? $currentOp;
			$dropdownId = "dropdown-op-$attr_class";

			$ret .= Html::beginTag('div', ['class' => 'dropdown d-inline-flex me-1']);
			$ret .= Html::button($currentIcon . ' <span class="visually-hidden">Operador</span>', [
				'class' => 'btn btn-outline-primary btn-sm dropdown-toggle',
				'type' => 'button',
				'data-bs-toggle' => 'dropdown',
				'aria-expanded' => 'false',
				'title' => $currentLabel,
			]);
			$ret .= Html::beginTag('ul', ['class' => 'dropdown-menu', 'aria-labelledby' => $dropdownId]);
			foreach (FormHelper::$operators as $op => $label) {
				$icon = FormHelper::$operatorIcons[$op] ?? $op;
				$active = $op === $currentOp ? ' active' : '';
				$ret .= Html::tag('li',
					Html::button($icon . ' ' . Html::tag('small', $label, ['class' => 'ms-2 text-muted']), [
						'class' => 'dropdown-item' . $active,
						'data-op' => $op,
						'data-target' => "op-$attr_class",
					]), ['class' => $active ? 'selected' : '']
				);
			}
			$ret .= Html::endTag('ul');
			$ret .= Html::endTag('div');
			$ret .= Html::hiddenInput("{$scope}[$attribute][op]", $currentOp, ['id' => "op-$attr_class"]);
			$this->view->registerJs("
				document.querySelectorAll('[data-target=\"op-$attr_class\"]').forEach(function(btn) {
					btn.addEventListener('click', function() {
						var op = this.dataset.op;
						document.getElementById('op-$attr_class').value = op;
						var dropdown = this.closest('.dropdown');
						var button = dropdown.querySelector('button');
						button.innerHTML = this.innerHTML.trim();
						button.title = this.querySelector('small').textContent;
						dropdown.querySelectorAll('.dropdown-item').forEach(function(item) {
							item.classList.remove('active');
						});
						this.classList.add('active');
						this.closest('li').classList.add('selected');
					});
				});
			");

			Html::addCssClass($this->options, 'd-flex form-control');
			Html::addCssStyle($this->options, [ 'width' => 'fit-content' ]);
			Html::addCssStyle($this->options, [ 'min-width' => '100px' ]);
			if ($inputValue === []) {
				$inputValue = '';
			}
			$ret .= Html::input($control_type, "{$scope}[$attribute][v]", $inputValue, $this->options);
		}
		return $ret;
	}

}
