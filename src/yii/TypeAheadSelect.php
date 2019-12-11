<?php
namespace santilin\churros\yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\InputWidget;
use dosamigos\typeahead\Bloodhound;
use dosamigos\typeahead\TypeAhead;

class TypeAheadSelect extends InputWidget
{

	protected $engine = null;
	public $relatedModel = null;
	public $searchFields = [];

	public function setBloodhound($field, $controller)
	{
		if( count($this->searchFields) == 0 ) {
			$codefield = $this->relatedModel->getModelInfo('code_field');
			if( $codefield != '' ) {
				$this->searchFields[] = $codefield;
			}
			$descfield = $this->relatedModel->getModelInfo('desc_field');
			if( $descfield != '' ) {
				$this->searchFields[] = $descfield;
			}
		}
		$this->engine = new Bloodhound([
			'name' => 'my_engine',
			'clientOptions' => [
				'datumTokenizer' => new \yii\web\JsExpression("Bloodhound.tokenizers.obj.whitespace('name')"),
				'queryTokenizer' => new \yii\web\JsExpression("Bloodhound.tokenizers.whitespace"),
				'identify' => new \yii\web\JsExpression("function(obj) { return obj.id; }"),
				'remote' => [
					'url' => Url::to([ "$controller/autocomplete", 'fields' => $this->searchFields, 'query'=>'QRY']),
					'wildcard' => 'QRY'
				]
			]
		]);
	}

	public function run()
    {
        if ($this->hasModel()) {
			echo Html::activeHiddenInput($this->model, $this->attribute);
			if (isset($this->options['name']) ) {
				$orig_name = $this->options['name'];
				unset($this->options['name']);
			} else {
				$orig_name = $this->attribute;
			}
			$name = $orig_name . "_autocomplete";
			if (isset($this->options['id']) ) {
				$orig_id = $this->options['id'];
				$this->options['id'] = $this->options['id'] . "_autocomplete";
			} else {
				throw new \Exception("No hay id");
				$this->options['id'] = $this->attribute . "_autocomplete_id";
			}
			$this->setBloodhound('id', $this->relatedModel->getModelInfo('controller_name'));
			$t = new TypeAhead( [
				'name' => $name,
				'options' => $this->options,
				'engines' => [ $this->engine ],
				'clientOptions' => [
					'highlight' => true,
					'minLength' => 2,
					'limit' => 10,
				],
				'clientEvents' => [
					'typeahead:selected' => "function (e, o) { console.log(o); $('#$orig_id').val(o.id); }"
				],
				'dataSets' => [
					[
						'name' => 'my_dataset',
						'displayKey' => 'id',
						'display' => 'value',
						'source' => $this->engine->getAdapterScript()
					]
				]

			]);
			echo $t->run();
        } else {
			throw new \Exception("to check");
        }
    }
}

