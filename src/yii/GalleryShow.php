<?php
namespace santilin\churros\yii;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use santilin\churros\helpers\AppHelper;

/**
 * Alert widget renders a message from session flash. All flash messages are
 * @author Santilin <software@noviolento.es>
 */
class GalleryShow extends \kartik\file\FileInput
{

	public $controller_url;
	public $caption;
	public $return = false;

    public function run()
    {
		if( $this->controller_url == '' || substr($this->controller_url,-1,1) == '/') {
			$this->controller_url .= $this->model->controllerName();
		}
		$images = Html::getAttributeValue($this->model, $this->attribute);
		try {
			$images_plugin_options = [
				'showUpload' => true,
				'showDrag' => false,
				'showRemove' => false,
				'initialPreviewAsData' => true,
				'initialCaption'=> "",
				'overwriteInitial' => true,
				'dropZoneEnabled' => false,
				'showClose' => false
			];
			if( $images != '' ) {
				$uns_images = unserialize($images);
				foreach( $uns_images as $filename => $titleandsize) {
					$deleteUrl = Url::to(["{$this->controller_url}/remove-image", 'field' => $this->attribute, 'id' => $this->model->getPrimaryKey(), 'filename' => $filename ]);
					$images_plugin_options['initialPreview'][] = "/uploads/" . $filename;
					$images_plugin_options['initialPreviewConfig'][] = [ 'caption' => $titleandsize[0], 'size' => $titleandsize[1], 'url' => $deleteUrl ];
				}
			}
			$this->pluginOptions = $images_plugin_options;
			$content = Html::tag('div', parent::run(), [ 'id' =>  "show-" . $this->options['id'] ]);
			$this->registerClientScript();
		} catch( \Exception $e ) {
			$content = $images;
		} catch( ErrorException $e ) {
			$content = $images;
		}
		if ($this->return) {
			return $content;
		} else {
			echo $content;
		}
	}

    public function registerClientScript()
    {
		$view = $this->getView();

		$id = "show-" . $this->options['id'];
		$js = "$('#$id .input-group').remove();";
		if (isset($this->options['caption'])) {
			$caption = $this->options['caption'];
			$js .= "$('#$id .file-preview').prepend('<p>$caption</p>');";
		}
		$view->registerJs($js);
    }

}
