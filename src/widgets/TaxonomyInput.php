<?php
namespace santilin\churros\widgets;
use yii\base\InvalidConfigException;
use yii\helpers\{Html};
use santilin\churros\ChurrosAsset;

class TaxonomyInput extends \yii\widgets\InputWidget
{
	public $taxonomy;
	public $input_id;
	protected $drop_ids;

	public function run()
    {
		if( empty($this->taxonomy['mask']) ) {
			throw new InvalidConfigException("The taxonomy mask must be set");
		}
		if( empty($this->taxonomy['dot']) ) {
			$this->taxonomy['dot'] = '.';
		}
		$levels = $this->taxonomy['levels'];
		$mask_groups = $this->maskToGroups();
		if( count($levels) != count($mask_groups) ) {
			throw new InvalidConfigException("The number of mask groups doesn't match the number of taxonomy levels");
		}
        if( !isset($this->options['id']) ) {
			$this->options['id'] = Html::getInputId($this->model, $this->attribute);
		}
        $html = '<table><tr><td></td>';
        foreach( $levels as $level) {
			$html .= "<td>{$level['title']}</td>";
		}
		$html .= "</tr><tr><td>";
		$html .= Html::activeInput('text', $this->model, $this->attribute, $this->options);
		$html .= '</td><td>';
        $options = $this->options;
        $name = $options['name']??Html::getInputName($this->model, $this->attribute);
		$options['id'] = "taxon_0_{$this->options['id']}";
		$this->drop_ids[] = $options['id'];
		$options['name'] = "taxon_{$name}[]";
		$options['data']['level'] = 0;
		$options['prompt'] = $levels[0]['prompt']??'Elige';
		$value = $this->getValueForLevel(0);
		$level0_values = $this->getLevelValues(0, $value);
		$html .= Html::dropDownList("taxon_0_{$name}", $value, $level0_values, $options);
		$html .= "</td>";
        $nlevels = count($this->taxonomy['levels']);
        for( $l=1; $l<$nlevels; ++$l) {
			$options['id'] = "taxon_{$l}_{$this->options['id']}";
			$this->drop_ids[] = $options['id'];
			$options['name'] = "taxon_{$name}[]";
			$options['data']['level'] = $l;
			$options['prompt'] = $levels[$l]['prompt']??'Elige';
			$html .= '<td>' . Html::dropDownList(null, [], [], $options) . '</td>';
		}
		$html .= '</tr></table>';
        $this->registerClientScript();
		$view = $this->getView();
        ChurrosAsset::register($view);
		return $html;
	}

	protected function getLevelValues($l, $value)
	{
		$values = [];
		if( $l == 0 ) {
			foreach( $this->taxonomy['items'] as $k => $v ) {
				$values[$k] = $v['title']??$k;
			}
		}
		return $values;
	}

	protected function getValueForLevel($l)
	{
		return '1';
	}



    /**
     * Registers the needed client script and options.
     */
    public function registerClientScript()
    {
        $view = $this->getView();
		$id = $this->options['id'];
		$j_id = str_replace('-','_',$id);
		$j_mask_groups = json_encode($this->maskToGroups());
		$j_taxonomy = json_encode($this->taxonomy);
		$j_drop_ids = json_encode($this->drop_ids);
		$js = <<<JS
const taxonomy_$j_id = $j_taxonomy;
const mask_groups_$j_id = $j_mask_groups;
const drop_ids_$j_id = $j_drop_ids;
$('#$id').focus( function() {
  var that = this;
  setTimeout(function(){ that.selectionStart = that.selectionEnd = 10000; }, 0);
});

$('#$id').keyup( function() {
	matchDropDownsToInput($(this));
});

function matchDropDownsToInput(j_input)
{
	const input_values = j_input.val().split(taxonomy_{$j_id}['dot']);
	console.log(input_values);
	for( level = 0; level < input_values.length; ++level ) {
		let dropdown = $('#' + drop_ids_{$j_id}[level]);
		dropdown.val( input_values[level] ).trigger('change');
	}
}

JS;
		$levels = $this->taxonomy['levels'];
		for( $l = 0; $l < count($this->taxonomy['levels'])-1; ++$l ) {
			$lplus1 = $l+1;
			$js .= <<<js
$('#taxon_{$l}_{$id}').change(function() {
	const input_values = $('#$id').val().split(taxonomy_{$j_id}['dot']);
	let next_dropdown = $('#taxon_{$lplus1}_{$id}');
 	next_dropdown.empty();
	const taxon_values = churros_dot_dot_taxon_values(taxonomy_$j_id, input_values, $lplus1);
	$.each(taxon_values, function(val, text) {
		next_dropdown.append($('<option>', {
			value: val,
			text : text
		}));
 	});
});
js;
		}

		$view->registerJs($js);
	}


	protected function maskToGroups()
	{
		$parts = explode($this->taxonomy['dot']??'.', $this->taxonomy['mask']);
		$ret = [];
		foreach( $parts as $part) {
			$ret[] = strlen($part);
		}
		return $ret;
	}

}
