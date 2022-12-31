<?php
namespace santilin\churros\widgets;
use yii\helpers\{ArrayHelper,Html};

class TaxonomyInput extends \yii\widgets\InputWidget
{
	public $taxonomy;

	public function run()
    {
        $this->registerClientScript();
        $name = ArrayHelper::remove($options, 'name', Html::getInputName($this->model, $this->attribute));
        $id= ArrayHelper::remove($options, 'id', Html::getInputName($this->model, $this->attribute));
        $value = Html::getAttributeValue($this->model, $this->attribute);
        $options = $this->options;
		$options['id'] = "taxon_0_$id";
		$level_titles = $this->taxonomy['levels'];
		$level_values = $this->getLevelValues(0, $value);
		$value = $this->getValueForLevel(0);
        $html = '<table><tr><td></td>';
        foreach( $level_titles as $title ) {
			$html .= "<td>$title</td>";
		}
		$html .= "</tr><tr><td>";
		$html .= Html::activeInput('text', $this->model, $this->attribute, $this->options);
		$html .= '</td><td>';
		$html .= Html::dropDownList("taxon_0_{$name}", $value, $level_values, $options);
		$html .= "</td>";
        $nlevels = count($this->taxonomy['levels']);
        for( $l=1; $l<$nlevels; ++$l) {
			$options['id'] = "taxon_{$l}_{$id}";
			$options['prompt'] = 'Elige';
			$html .= '<td>' . Html::dropDownList(null, [], [], $options) . '</td>';
		}
		$html .= '</tr></table>';
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
		$js = <<<JS
$('#$id').focus( function() {
  var that = this;
  setTimeout(function(){ that.selectionStart = that.selectionEnd = 10000; }, 0);
});
JS;
		$view->registerJs($js);
	}

}
