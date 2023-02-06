<?php
namespace santilin\churros\widgets;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use santilin\churros\helpers\AppHelper;

/**
 * A blend of FileInput and GalleryShow
 *
 * @author Santilin <software@noviolento.es>
 */
class KartikImageInput extends \kartik\file\FileInput
{
	const ATTR_URL_VERBATIM = 0;
	const ATTR_URL_UPLOAD_BEHAVIOR = 1;
	const ATTR_URL_SERIALIZED = 2;

	public $caption;
	public $deleteCheck = false;
	public $attrUrlType = self::ATTR_URL_VERBATIM;

    /**
     * {@inheritdoc}
     */
    public function run()
    {
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
		$images = [];
		switch( $this->attrUrlType ) {
		case self::ATTR_URL_VERBATIM:
			$this->pluginOptions['initialPreview'][] = Html::getAttributeValue($this->model, $this->attribute);
			break;
		case self::ATTR_URL_UPLOAD_BEHAVIOR:
 			$img_data = $this->model->getUploadedFormFileUrl($this->attribute);
 			if( $img_data ) {
				$this->pluginOptions['initialPreview'][] = $img_data;
			}
			break;
		case self::ATTR_URL_SERIALIZED:
			$serialized = Html::getAttributeValue($this->model, $this->attribute);
			if( $serialized != '' && is_string($serialized))  {
				$images = @unserialize($serialized);
				if ($images === false ) {
					$images = [ $serialized ];
				}
				foreach( $images as $filename ) {
					$this->pluginOptions['initialPreview'][] = Yii::getAlias("@uploads/$filename");
				}
			}
			break;
		}
		$parent_file_input = parent::run();
		if( $this->deleteCheck !== false && !empty($this->model->{$this->attribute}) ) {
			if( $this->deleteCheck == true ) {
				$deleteCheckOptions = [ 'label' => 'Delete me: ' . $this->model->{$this->attribute} ];
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
