<?php
namespace santilin\churros\yii;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use santilin\churros\helpers\AppHelper;
use santilin\churros\yii\GalleryShow;

/**
 * A blend of FileInput and GalleryShow
 *
 * @author Santilin <software@noviolento.es>
 */
class ImageInput extends \kartik\file\FileInput
{

	public $controller_url;
	public $caption;
	public $show_caption;

    /**
     * {@inheritdoc}
     */
    public function run()
    {
		if( $this->controller_url == '' || substr($this->controller_url,-1,1) == '/') {
			$this->controller_url .= $this->model->controllerName();
		}
		$this->pluginOptions = [
			'showUpload' => false,
			'showRemove' => false,
			'initialPreviewShowDelete' => false,
			// 'showDownload' => true, Tiene que estar definida la función de descargar
			'fileActionSettings' => [
				'showDrag' => false,
				'showZoom' => true,
			],
			'initialPreviewAsData' => true,
			'overwriteInitial' => true
		];
		$images = Html::getAttributeValue($this->model, $this->attribute);
		if( $images != '' )  {
			$images = unserialize($images);
			foreach( $images as $filename ) {
				$this->pluginOptions['initialPreview'][] = Yii::getAlias('@web') . '/uploads/' . $filename;
			}
		}
		echo Html::tag('div', parent::run());
	}

}
