<?php
namespace santilin\churros\widgets;
use yii\base\InvalidConfigException;
use yii\helpers\{Html};
use yii\web\View;
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
		if( count($levels) > count($mask_groups) ) {
			throw new InvalidConfigException("The number of levels can't be greater than the number of mask groups");
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
const drop_ids_$j_id = $j_drop_ids;
$('#$id').focus( function() {
  var that = this;
  setTimeout(function(){ that.selectionStart = that.selectionEnd = 10000; }, 0);
});

$('#$id').keyup( function(e) {
	const is_backspace = (e.keyCode == 8 || e.keyCode == 46);
	const printable = is_backspace ||
		(e.keyCode > 47 && e.keyCode < 58)   || // number keys
		e.keyCode == 32 || e.keyCode == 13   || // spacebar & return key(s) (if you want to allow carriage returns)
		(e.keyCode > 64 && e.keyCode < 91)   || // letter keys
		(e.keyCode > 95 && e.keyCode < 112)  || // numpad keys
		(e.keyCode > 185 && e.keyCode < 193) || // ;=,-./` (in order)
		(e.keyCode > 218 && e.keyCode < 223);   // [\]' (in order)
	if( printable ) {
		matchDropDownsToInput($(this), '$id', taxonomy_$j_id, drop_ids_$j_id);
	}
	return true;
});

function split_by_dot(str, dot)
{
	const ret = str.split(dot).filter(function(i) { return i });
	console.log(str, dot, '=>', ret);
	return ret;
}

function taxonomy_values(taxonomy, values, level)
{
	const levels = taxonomy.levels;
	let options = taxonomy.items;
 	// find the options for input[level]
	for( l=0; l<level; ++l ) {
		if( values[l] === undefined ) {
			break;
		}
		if( options[values[l]] === undefined ) {
			return [ [ '', 'Valor inválido' ] ];
		}
		console.log('Antes', options, values[l], options[values[l]]);
		options = options[values[l]].items;
		if( options === undefined ) {
			break;
		}
	}
	if( options !== undefined && Object.keys(options).length ) { // https://stackoverflow.com/a/6700
		let ret = [ [ '', 'Elige...' ] ];
		for( const v in options ) {
			ret.push([ v, options[v].title ]);
		}
		return ret;
	} else {
		return [];
	}
}

function matchDropDownsToInput(j_input, id, taxonomy, drop_ids)
{
	const input_values = split_by_dot(j_input.val(), taxonomy['dot']);
	if( input_values.length ) {
		for( level = 0; level < input_values.length && level < taxonomy.levels.length; ++level ) {
			let dropdown = $('#' + drop_ids[level]);
			dropdown.val( input_values[level] );
			let next_dropdown = $('#taxon_' + (level + 1 ) + '_' + id);
			change_dropdown(next_dropdown, taxonomy, input_values, level + 1);

		}
	} else {
		let dropdown = $('#' + drop_ids[0]);
		dropdown.val('');
	}

}

function change_dropdown(next_dropdown, taxonomy, input_values, level)
{
 	next_dropdown.empty();
	const taxon_values = taxonomy_values(taxonomy, input_values, level);
	if( taxon_values.length == 0 ) {
		next_dropdown.append($('<option>', {
			value: '0',
			text : 'No hay valores',
			selected: true
		}));
	} else {
		$.each(taxon_values, function(k, value) {
			next_dropdown.append($('<option>', {
				value: value[0],
				text : value[1]
			}));
		});
	}
}
JS;
		$levels = $this->taxonomy['levels'];
		for( $l = 0; $l < count($this->taxonomy['levels']); ++$l ) {
			$lplus1 = $l+1;
			$js .= <<<js
$('#taxon_{$l}_{$id}').change(function() {
	const input_values = split_by_dot($('#$id').val(), taxonomy_{$j_id}['dot']).slice(0, $l);
	input_values[$l] = $(this).val();
	$('#$id').val( input_values.join(taxonomy_{$j_id}['dot']) );
	let next_dropdown = $('#taxon_{$lplus1}_$id');
	change_dropdown(next_dropdown, taxonomy_{$j_id}, input_values, $lplus1);
});

js;
		}
		$view->registerJs($js);

		$ready_js = <<<js
// inicial
matchDropDownsToInput($('#$id'), '$id', taxonomy_$j_id, drop_ids_$j_id);
js;
		$view->registerJs($ready_js, View::POS_READY);

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
