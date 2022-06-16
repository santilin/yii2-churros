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
class FileInput extends \kartik\file\FileInput
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
		$delete_check = '';
		$attr_value = trim($this->model->{$this->attribute});
		if( $attr_value ) {
			switch( $this->attrUrlType ) {
			case self::ATTR_URL_VERBATIM:
				$this->pluginOptions['initialPreview'][] = $attr_value;
				break;
			case self::ATTR_URL_UPLOAD_BEHAVIOR:
				$this->pluginOptions['initialPreview'][] =
				$this->model->getUploadedFileUrl($this->attribute);
				break;
			case self::ATTR_URL_SERIALIZED:
				$serialized = $attr_value;
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
			if( $this->deleteCheck !== false ) {
				if( $this->deleteCheck == true ) {
					$deleteCheckOptions = [ 'label' => 'Delete me: ' . $this->model->{$this->attribute} ];
				} else {
					$deleteCheckOptions = $this->deleteCheck;
				}
				$delete_check = Html::checkbox(Html::getInputName($this->model, $this->attribute),
					false, $deleteCheckOptions);
			}
		}
		$parent_file_input = parent::run();
		echo Html::tag('div', $parent_file_input . $delete_check);
	}

}
