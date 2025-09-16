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
	public $deleteFileName = ':delete_me:';
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
		switch( $this->attrUrlType) {
		case self::ATTR_URL_VERBATIM:
			$img_value = Html::getAttributeValue($this->model, $this->attribute);
			$this->pluginOptions['initialPreview'][] = $img_value;
			break;
		case self::ATTR_URL_UPLOAD_BEHAVIOR:
 			$img_value = $this->model->getUploadedFormFileUrl($this->attribute);
 			if ($img_value) {
				$this->pluginOptions['initialPreview'][] = $img_value;
			}
			break;
		case self::ATTR_URL_SERIALIZED:
			$img_value = Html::getAttributeValue($this->model, $this->attribute);
			if ($img_value != '' && is_string($img_value))  {
				$images = @unserialize($img_value);
				if ($images === false) {
					$images = [ $img_value ];
				}
				foreach ($images as $filename) {
					$this->pluginOptions['initialPreview'][] = Yii::getAlias("@uploads/$filename");
				}
			}
			break;
		}
		if ($img_value) {
			$input_name = Html::getInputName($this->model, $this->attribute);
			$this->pluginEvents['fileclear'] = new \yii\web\JsExpression(<<<js

function(e) {
	$("input[name='$input_name'][type='hidden']").val("{$this->deleteFileName}");
	return true;
}
js
			);
		}
		echo parent::run();
	}

}
