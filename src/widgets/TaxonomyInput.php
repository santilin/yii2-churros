<?php
namespace santilin\churros\widgets;
use yii\base\InvalidConfigException;
use yii\helpers\{Html};
use yii\web\View;

class TaxonomyInput extends \yii\widgets\InputWidget
{
	public $taxonomy;
	public $showCode = true;
	public $hideFirstLabel = false;
	public $taxonomyLevel = -1;
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
		$html = '';
//         $html = '<div class="row"><div class="col col-md-9 col-lg-9 col-xxl-9 px-1 mb-1"><div class="row">';
		if( $this->showCode ) {
			$html .= Html::activeInput('text', $this->model, $this->attribute, $this->options);
		} else {
			$html .= Html::activeHiddenInput($this->model, $this->attribute);
		}
// 		$html .= '</div>';
		$html .= '<div class="row taxonomy-dropdowns">';
		if( $this->taxonomyLevel <= 0 || $this->taxonomyLevel > count($this->taxonomy['levels']) ) {
			$nlevels = count($this->taxonomy['levels']);
		} else {
			$nlevels = $this->taxonomyLevel;
		}
		$dropdown_container_classes = 'col col-md-6 col-sm-6 col-12 col-lg-4 col-xl-3';
		$headings = [];
        foreach( $levels as $k => $level) {
			if( $k >= $nlevels )  {
				break;
			}
			if( $k == 0 && $this->hideFirstLabel ) {
				$headings[$k] = '';
			} else {
				$headings[$k] = $level['title'];
			}
		}
		$html .= "<div class=\"$dropdown_container_classes\">";
		$options = $this->options;
		$name = $options['name']??Html::getInputName($this->model, $this->attribute);
		$options['id'] = "taxon_0_{$this->options['id']}";
		$this->drop_ids[] = $options['id'];
		$options['name'] = "taxon_{$name}[]";
		$options['data']['level'] = 0;
		$options['prompt'] = $levels[0]['prompt']??'Elige';
		$value = $this->getValueForLevel(0);
		$level0_values = $this->getLevelValues(0, $value);
 		$html .= "<label class=\"taxonomy level-0\">{$headings[0]}</label> " . Html::dropDownList("taxon_0_{$name}", $value, $level0_values, $options);
 		$html .= "</div>";
        for( $l=1; $l<$nlevels; ++$l) {
			$options['id'] = "taxon_{$l}_{$this->options['id']}";
			$this->drop_ids[] = $options['id'];
			$options['name'] = "taxon_{$name}[]";
			$options['data']['level'] = $l;
			$options['prompt'] = $levels[$l]['prompt']??'Elige';
 			$html .= "<div class=\"$dropdown_container_classes\"><label class=\"taxonomy level-$l\">{$headings[$l]}</label> "
				. Html::dropDownList(null, [], [], $options) . '</div>';
		}
 		$html .= '</div><!--row-->';
        $this->registerClientScript();
		$view = $this->getView();
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
	if (!str) {
		return [];
	}
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
	empty_all_dropdowns();
	const input_values = split_by_dot(j_input.val(), taxonomy['dot']);
	if( input_values.length ) {
		for( level = 0; level < input_values.length && level < taxonomy.levels.length; ++level ) {
			let dropdown = $('#' + drop_ids[level]);
			dropdown.val( input_values[level] );
			let next_dropdown = $('#taxon_' + (level + 1 ) + '_' + id);
			if( next_dropdown.length != 0 ) {
				update_dropdown(next_dropdown, taxonomy, input_values, level + 1);
			}
		}
	} else {
		let dropdown = $('#' + drop_ids[0]);
		dropdown.val('');
	}

}

function update_dropdown(this_dropdown, taxonomy, input_values, level)
{
 	this_dropdown.empty();
	const taxon_values = taxonomy_values(taxonomy, input_values, level);
	if( taxon_values.length == 0 ) {
		this_dropdown.append($('<option>', {
			value: '0',
			text : 'No hay valores',
			selected: true
		}));
		// Cuando no hay valores, añadir {dot}0 al código si hace falta
		const input_values = split_by_dot($('#$id').val(), taxonomy_{$j_id}['dot']);
		if( level < taxonomy_{$j_id}['levels'].length && level == input_values.length ) {
			$('#$id').val( input_values.join(taxonomy_{$j_id}['dot']) + taxonomy_{$j_id}['dot'] + '0');
		}
	} else {
		$.each(taxon_values, function(k, value) {
			this_dropdown.append($('<option>', {
				value: value[0],
				text : value[1]
			}));
		});
	}
}

JS;
		// Each dropdown has its own change handler
		$taxonomy_levels = $this->taxonomy['levels'];
		$js_empty_dropdown = [];
		for( $l = 0; $l < count($taxonomy_levels); ++$l ) {
			$lplus1 = $l+1;
			if( $l>0 ) {
				$js_empty_dropdown[] = <<<js
$('#taxon_{$l}_{$id}').empty();
js;
			}

			$js .= <<<js
$('#taxon_{$l}_{$id}').change(function() {
	const input_values = split_by_dot($('#$id').val(), taxonomy_{$j_id}['dot']).slice(0, $l);
	input_values[$l] = $(this).val();
	$('#$id').val( input_values.join(taxonomy_{$j_id}['dot']) );
	let next_dropdown = $('#taxon_{$lplus1}_$id');
	if( next_dropdown.length != 0 ) {
		update_dropdown(next_dropdown, taxonomy_{$j_id}, input_values, $lplus1);
	}
});

js;
			$js .= 'function empty_all_dropdowns() { ' . implode('', $js_empty_dropdown) . "}\n";
		}
		$view->registerJs($js);

		$ready_js = <<<js
// initial
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
