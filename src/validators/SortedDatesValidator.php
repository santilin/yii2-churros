<?php
namespace santilin\churros\validators;

use santilin\churros\helpers\YADTC;
use yii\validators\Validator;
use yii\base\DynamicModel;
use yii\validators\DateValidator;
use yii\base\InvalidConfigException;
use Yii;

/**
 * Checks that a list of date fields is in order, each one not earlier than the
 * one preceding it: `inicio <= revision <= fin`.
 *
 * Unlike DateRangeValidator, which compares two fields, here the list is of free
 * length and the rule is declared once, over all the attributes:
 *
 *     [['inicio', 'revision', 'fin'], SortedDatesValidator::class]
 *
 * Empty fields do not break the chain: they are skipped and the comparison
 * continues with the next one that has a value, which is what is needed when the
 * intermediate ones are optional. The error is set on the field that breaks the
 * order.
 */
class SortedDatesValidator extends Validator
{
	/**
	 * @var string date type, as in DateRangeValidator: TYPE_DATE, TYPE_DATETIME
	 * or TYPE_TIME
	 */
	public $type = DateValidator::TYPE_DATE;

	public $formatDate = 'php:' . YADTC::SQL_DATE_FORMAT;
	public $formatDateTime = 'php:' . YADTC::SQL_DATETIME_FORMAT;
	public $formatTime = 'php:' . YADTC::SQL_TIME_FORMAT;

	/**
	 * @var bool whether two equal dates break the order. By default no: what is
	 * required is that they do not go backwards
	 */
	public $strict = false;

	/**
	 * @var string the error message. Markers: {label}, {date}, {previous-label} and
	 * {previous}
	 */
	public $message = null;

	/**
	 * The whole list is validated at once, not attribute by attribute: the order is
	 * a property of the set.
	 */
	public function validateAttributes($model, $attributes = null)
	{
		$attributes = $this->getValidationAttributes($attributes);
		if (count($attributes) < 2) {
			throw new InvalidConfigException(
				'SortedDatesValidator: se necesitan al menos dos atributos');
		}
		$format = match($this->type) {
			DateValidator::TYPE_DATE => $this->formatDate,
			DateValidator::TYPE_TIME => $this->formatTime,
			default                  => $this->formatDateTime,
		};

		$previous_attribute = null;
		$previous_timestamp = null;
		foreach ($attributes as $attribute) {
			if ($this->skipOnError && $model->hasErrors($attribute)) {
				continue;
			}
			$value = $model->$attribute;
			if ($this->isEmpty($value)) {
				continue;
			}
			$parsed = DynamicModel::validateData(
				[ $attribute => $value, 'parsed' ],
				[ [ $attribute, $this->type, 'format' => $format,
					'timestampAttribute' => 'parsed' ] ]
			);
			if ($parsed->hasErrors()) {
				// The date is not valid: the date validator of the field itself will report it
				continue;
			}
			if ($previous_timestamp !== null
				&& ($this->strict
					? $parsed->parsed <= $previous_timestamp
					: $parsed->parsed < $previous_timestamp)) {
				$this->addError($model, $attribute, $this->message ?: Yii::t('churros',
					'{label} {date} can\'t be earlier than {previous-label} {previous}'), [
						'label' => $model->getAttributeLabel($attribute),
						'previous-label' => $model->getAttributeLabel($previous_attribute),
						'date' => $this->formatValue($value),
						'previous' => $this->formatValue($model->$previous_attribute),
					]);
				return;
			}
			$previous_attribute = $attribute;
			$previous_timestamp = $parsed->parsed;
		}
	}

	protected function formatValue($value): string
	{
		return match($this->type) {
			DateValidator::TYPE_DATE => Yii::$app->formatter->asDate($value),
			DateValidator::TYPE_TIME => Yii::$app->formatter->asTime($value),
			default                  => Yii::$app->formatter->asDateTime($value),
		};
	}
}
