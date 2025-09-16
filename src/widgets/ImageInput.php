<?php
namespace santilin\churros\widgets;

use Yii;
use yii\helpers\{Html,Url};
use yii\base\InvalidConfigException;
use santilin\churros\helpers\AppHelper;

/**
 * A blend of FileInput and GalleryShow
 *
 * @author Santilin <software@noviolento.es>
 */
class ImageInput extends \yii\widgets\InputWidget
{
	const ATTR_URL_VERBATIM = 0;
	const ATTR_URL_UPLOAD_BEHAVIOR = 1;
	const ATTR_URL_SERIALIZED = 2;

	public $caption;
	public $form = null;
	public $thumbSize = '100px';
	public $deleteCheck = false;
	public $attrUrlType = self::ATTR_URL_VERBATIM;

	public function init()
	{
		if (!$this->form) {
			throw new InvalidConfigException('ImageInput::form must be set');
		}
		return parent::init();
	}

    /**
     * {@inheritdoc}
     */
    public function run()
    {
		$thumb_options = [ 'width' => $this->thumbSize ];
		switch( $this->attrUrlType) {
		case self::ATTR_URL_VERBATIM:
			$src = Html::getAttributeValue($this->model, $this->attribute);
			if ($src) {
				echo Html::img($src, $thumb_options);
			}
			break;
		case self::ATTR_URL_UPLOAD_BEHAVIOR:
			$src = $this->model->getUploadedFileUrl($this->attribute);
			if ($src) {
				echo Html::img($src, $thumb_options);
			}
			break;
		case self::ATTR_URL_SERIALIZED:
			$serialized = Html::getAttributeValue($this->model, $this->attribute);
			if ($serialized != '' && is_string($serialized))  {
				$images = @unserialize($serialized);
				if ($images === false) {
					$images = [ $serialized ];
				}
				foreach( $images as $filename) {
					echo Html::img($filename, $thumb_options);
				}
			}
			break;
		}
		if (!isset($this->form->options['enctype'])) {
			$this->form->options['enctype'] = 'multipart/form-data';
		}
		$parent_file_input =  $this->renderInputHtml('file');
		if ($this->deleteCheck !== false && !empty($this->model->{$this->attribute})) {
			if ($this->deleteCheck == true) {
				$deleteCheckOptions = [ 'label' => Yii::t('churros','Delete this image') . ': ' . $this->model->{$this->attribute} ];
			} else {
				$deleteCheckOptions = $this->deleteCheck;
			}
			$delete_check = Html::checkbox(Html::getInputName($this->model, $this->attribute),
				false, $deleteCheckOptions);
		} else {
			$delete_check = '';
		}
		echo Html::tag('div', $delete_check . $parent_file_input);
	}

}
