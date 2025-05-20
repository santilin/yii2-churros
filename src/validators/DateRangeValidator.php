<?php
/**
 * Adapted from https://github.com/nerburish/yii2-daterange-validator
 */
namespace santilin\churros\validators;
use santilin\churros\helpers\YADTC;
use yii\validators\Validator;
use yii\base\DynamicModel;
use yii\validators\DateValidator;
use yii\base\InvalidConfigException;
use Yii;

class DateRangeValidator extends Validator
{
    /**
     * @var string the type of the validator that will be used by DateValidator from Yii2 core validator.
	 * If is set as 'date' type, the from date time will be set as 00:00:00 and until date time as 23:59:59.
	 * If is set as 'datetime' type, the from and until times will be preserved as passed.
	 * However, if set as format 'time' or any else will throw yii\base\InvalidConfigException
	 * If not set, type 'date' will be used.
     */
    public $type = DateValidator::TYPE_DATE;

    /**
     * @var string the date format expcted by DateValidator from Yii2 core validator.
     *
     * If not set, will be used the format defined in formatter component in the same way as DateValidator does
     * See DateValidator format: http://www.yiiframework.com/doc-2.0/yii-validators-datevalidator.html#
     */
    public $formatDate = 'php:' . YADTC::SQL_DATE_FORMAT;
    public $formatDateTime = 'php:' . YADTC::SQL_DATETIME_FORMAT;

    /**
     * @var string attribute name of the model passed where the until date timestamp will be assigned
     */
	public $untilAttribute = null;

    /**
     * @inheritdoc
     */
	public function validateAttribute($model, $attribute)
    {
		if( $this->untilAttribute == '' ) {
			throw new InvalidConfigException('DateRangeValidator: untilAttribute not set');
		}
		$format = $this->type == DateValidator::TYPE_DATE ? $this->formatDate : $this->formatDateTime;
		$fromDate = $model->$attribute;
		$untilDate = $model->{$this->untilAttribute};


		$validationModel = DynamicModel::validateData(
			[
				$attribute => $fromDate,
				$this->untilAttribute => $untilDate,
				'parsedFrom',
				'parsedUntil'
			],
			[
				[$attribute, $this->type, 'format' => $format,
					'timestampAttribute' => 'parsedFrom'],
				[$this->untilAttribute, $this->type, 'format' => $format,
					'timestampAttribute' => 'parsedUntil'],
			]
		);

		if ($validationModel->hasErrors()) {
			$s_err = '';
			$k_err = null;
			foreach ($validationModel->getErrors() as $k => $error) {
				if (!$k_err) {
					$k_err = $k;
				}
				if ($s_err) {
					$s_err .= ' ';
				}
				$s_err .= $error[0];
			}
			return $this->addError($model, $k, $s_err);
		}

		if ($validationModel->parsedFrom && $validationModel->parsedUntil) {
			if( $validationModel->parsedFrom > $validationModel->parsedUntil ) {
				return $this->addError($model, $attribute, Yii::t('churros',
					'{from-label} {from} can\'t be greater than {until-label} {until}', [
						'from-label' => $model->getAttributeLabel($attribute),
						'until-label' => $model->getAttributeLabel($this->untilAttribute),
						'from' => Yii::$app->formatter->asDateTime($fromDate),
						'until' => Yii::$app->formatter->asDateTime($untilDate)]));
			}
		}
		return true;
    }
}
