<?php
namespace santilin\churros\validators;

use santilin\churros\helpers\YADTC;
use yii\validators\Validator;
use yii\base\DynamicModel;
use yii\validators\DateValidator;
use yii\base\InvalidConfigException;
use Yii;

/**
 * Comprueba que una lista de campos de fecha va en orden, cada uno no anterior al que
 * le precede: `inicio <= revision <= fin`.
 *
 * A diferencia de DateRangeValidator, que compara dos campos, aquí la lista es de
 * longitud libre y la regla se declara una sola vez, sobre todos los atributos:
 *
 *     [['inicio', 'revision', 'fin'], SortedDatesValidator::class]
 *
 * Los campos vacíos no cortan la cadena: se saltan y la comparación sigue con el
 * siguiente que tenga valor, que es lo que hace falta cuando los intermedios son
 * opcionales. El error se pone en el campo que rompe el orden.
 */
class SortedDatesValidator extends Validator
{
	/**
	 * @var string tipo de fecha, como en DateRangeValidator: TYPE_DATE, TYPE_DATETIME
	 * o TYPE_TIME
	 */
	public $type = DateValidator::TYPE_DATE;

	public $formatDate = 'php:' . YADTC::SQL_DATE_FORMAT;
	public $formatDateTime = 'php:' . YADTC::SQL_DATETIME_FORMAT;
	public $formatTime = 'php:' . YADTC::SQL_TIME_FORMAT;

	/**
	 * @var bool si dos fechas iguales rompen el orden. Por defecto no: lo que se pide
	 * es que no vayan hacia atrás
	 */
	public $strict = false;

	/**
	 * @var string el mensaje de error. Marcadores: {label}, {date}, {previous-label} y
	 * {previous}
	 */
	public $message = null;

	/**
	 * Se valida la lista entera de una vez, no atributo a atributo: el orden es una
	 * propiedad del conjunto.
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
				// La fecha no vale: el validador de fecha del propio campo ya lo dirá
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
