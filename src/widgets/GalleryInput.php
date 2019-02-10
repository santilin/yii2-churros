<?php
namespace santilin\Churros\widgets;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * A blend of FileInput and GalleryShow
 *
 * @author Santilin <software@noviolento.es>
 */
class GalleryInput extends \kartik\file\FileInput
{

	public $controller_url;
	public $caption;
	public $show_caption;

    /**
     * {@inheritdoc}
     */
    public function run()
    {
		$controller = isset($this->controller_url)?$this->controller_url:strtolower($this->model->classname());
		$this->pluginOptions = [
				'showUpload' => false,
				'showDrag' => true,
				'showRemove' => true,
				'initialPreviewAsData' => true,
				'overwriteInitial' => true
		];
		$images = Html::getAttributeValue($this->model,
                $this->attribute);
		if( $images != '' )  {
			$show_options = $this->options;
			$show_options['id'] = 'fake-' . $show_options['id'];
			if (isset($this->options['caption'])) {
				$show_options['caption'] = $this->options['show_caption'];
			}
			echo \app\widgets\GalleryShow::widget(
					['controller_url' => $this->controller_url,
					 'model' => $this->model,
					 'attribute' => $this->attribute,
 					 'options' => $show_options,
					]);
		}
		echo Html::tag('div', parent::run(), [ 'id' =>  "input_" . $this->options['id'] ]);
		$this->registerClientScript();
	}

    public function registerClientScript()
    {
		if (isset($this->options['caption'])) {
			$caption = $this->options['caption'];
			$view = $this->getView();

			$id = "input_" . $this->options['id'];
			$js = "$('#$id .file-preview').prepend('<p>$caption</p>');";
			$view->registerJs($js);
		}
    }

}
