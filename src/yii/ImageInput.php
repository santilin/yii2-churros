<?php
namespace santilin\churros\yii;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use santilin\churros\helpers\AppHelper;

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
			'language' => 'es',
			'showUpload' => false,
			'showRemove' => true,
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
		if( $images != '' && is_string($images))  {
			$images_uns = @unserialize($images);
			if ($images_uns !== false ) {
				foreach( $images_uns as $filename ) {
					$this->pluginOptions['initialPreview'][] = Yii::getAlias('@web') . '/uploads/' . $filename;
				}
			} else {
				$this->pluginOptions['initialPreview'][] = Yii::getAlias('@web') . '/uploads/' . $images;
			}
		} else {
		 	$this->model->setAttribute($this->attribute, null);
		}
		echo Html::tag('div', parent::run());
	}

}
