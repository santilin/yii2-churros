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
	public $deleteCheck = false;

    /**
     * {@inheritdoc}
     */
    public function run()
    {
		if( $this->controller_url == '' || substr($this->controller_url,-1,1) == '/') {
			$this->controller_url .= $this->model->controllerName();
		}
		$this->pluginOptions = [
			'language' => substr(Yii::$app->language, 0, 2),
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
					$this->pluginOptions['initialPreview'][] = Yii::getAlias("@uploads/$filename");
				}
			} else {
				$this->pluginOptions['initialPreview'][] = Yii::getAlias("@uploads/$images");
			}
		} else {
		 	$this->model->setAttribute($this->attribute, null);
		}
		$parent_file_input = parent::run();
		if( $this->deleteCheck !== false ) {
			if( $this->deleteCheck == true ) {
				$deleteCheckOptions = [ 'label' => 'Delete me' ];
			} else {
				$deleteCheckOptions = $this->deleteCheck;
			}
			$delete_check = Html::checkbox(Html::getInputName($this->model, $this->attribute),
				false, $deleteCheckOptions);
		} else {
			$delete_check = '';
		}
		
		echo Html::tag('div', $parent_file_input . $delete_check);
	}

}
