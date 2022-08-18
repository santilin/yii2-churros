<?php
namespace santilin\churros\yii;

use yii\helpers\Html;
use santilin\churros\yii\RadioImagesAsset;

// https://stackoverflow.com/questions/18796221/creating-a-select-box-with-a-search-option/56590636#56590636

class RadioImagesInput extends \yii\widgets\InputWidget
{
	public $items;
	public $images;

	public function init()
	{
		parent::init();
        if (!array_key_exists('id', $this->options)) {
            $this->options['id'] = Html::getInputId($this->model, $this->attribute);
        }
	}

    public function run()
    {
        $view = $this->getView();
        RadioImagesAsset::register($view);
        return "<div class=\"radio-images\" class=\"radio-images\"><ul>" . $this->renderRadios() . '</ul></div>';
    }


	public function renderRadios()
	{
		$radios = [];
		$id = $this->options['id'];
		$n = 0;
		foreach( $this->items as $value => $item ) {
			$radio = '<li>' . Html::activeRadio($this->model, $this->attribute, [
				'uncheck' => false, 'label' => false, 'id' => "{$id}-{$n}", "checked" => '', 'value' => $value,
			]);
			if( empty($this->images[$value]) ) {
				$radio .= Html::tag('label', $item,
					[ 'for' => "{$id}-{$n}" ]);
			} else {
				$radio .= Html::tag('label', Html::img('@web/img/'.$this->images[$value],
					[ 'for' => "{$id}-{$n}" ]));
			}
			$radio .= '</li>';
			++$n;
			$radios[] = $radio;
		}
		return implode(' ', $radios);
	}

} // class
