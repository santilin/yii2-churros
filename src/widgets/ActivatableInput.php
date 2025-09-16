<?php

namespace santilin\churros\widgets;

use Yii;
use yii\helpers\{Html,ArrayHelper};

/**
 */

class ActivatableInput extends \kartik\datecontrol\DateControl
{
	public $labels = [];

    /**
     * {@inheritdoc}
     */
    public function run()
    {
		if ($this->labels == []) {
			$this->labels = [ Yii::t('churros', 'Inactive'), Yii::t('churros', 'Active') ];
		}
		$this->value = Html::getAttributeValue($this->field->model, $this->field->attribute);
		if (!isset($this->widgetOptions['layout'])) {
			if ($this->value == '') {
				$active = $this->labels[1];
			} else {
				$active = $this->labels[0];
			}
			$this->options['label'] = $active;
		}
		echo parent::run();
	}

	/*
	 * redefinida para manejar el caso de un widget datetime y un valor solo date
	 */
    public function getDisplayValue($data)
    {
		$disp_value = parent::getDisplayValue($data);
		if ($disp_value == '' && $this->value != '' && $this->type === self::FORMAT_DATETIME) {
			$_doTranslate = (isset($this->language) && $this->language != 'en');
			$saveFormat = substr($this->saveFormat, 0, strpos($this->saveFormat, ' '));
			$settings = $_doTranslate ? ArrayHelper::getValue($this->pluginOptions, 'dateSettings', []) : [];
			$date = static::getTimestamp($this->value, $saveFormat, $this->saveTimezone, $settings);
			if ($date && $date instanceof \DateTime) {
				if ($this->displayTimezone != null) {
					$date->setTimezone(new \DateTimeZone($this->displayTimezone));
				}
				$value = $date->format($this->displayFormat);
				if ($_doTranslate) {
					$value = $this->translateDate($value, $this->displayFormat);
				}
				return $value;
			}
		}
        return $disp_value;
    }
}

