<?php

namespace santilin\churros\validators;

use Yii;
use yii\validators\Validator;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * RangeValidator validates that the attribute value is among a list of values.
 *
 * The range can be specified via the [[range]] property.
 * If the [[not]] property is set true, the validator will ensure the attribute value
 * is NOT among the specified range.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class DotDotValidator extends Validator
{
    public $mask = false;
    public $dot = '.';


    public function init()
    {
        parent::init();
        if ($this->message === null) {
             $this->message = Yii::t('yii', "{attribute} '{value}' does not match the mask {$this->mask}.");
        }
    }

    public function validateAttribute($model, $attribute)
    {
		$value = $model->{$attribute};
		if( $this->validateValue($value) === null ) {
			$model->{$attribute} = $this->formatValue($value);
		} else {
			$this->addError($model, $attribute, $this->message);
		}
	}

    /**
     * {@inheritdoc}
     */
    protected function validateValue($value)
    {
		if( $this->mask == '' ) {
			return true;
		}
		$mask_groups = $this->maskToGroups($this->mask, $this->dot);
		$regexp_dot = $this->dot;
		if( $this->dot == '.' ) {
			$regexp_dot = '\\.';
		}
		$reg_exps = [];
		for( $i=0; $i<count($mask_groups); ++$i ) {
			if( $i==0 ) {
				$reg_exps[] = "[0-9]{1," . $mask_groups[$i] . "}";
			} else {
				$reg_exps[] = $regexp_dot . "[0-9]{0," . $mask_groups[$i] . "}";
			}
		}
		$re_str = '';
		for( $i=0; $i<count($reg_exps); ++$i ) {
			if( $i>0 ) {
				$re_str .= '|';
			}
			for( $j=0; $j<=$i; ++$j ) {
				$re_str .= $reg_exps[$j];
			}
		}
		if( preg_match('/^(' . $re_str . ')$/', $value ) ) {
			return null;
		} else {
			return [ $this->message, [] ];
		}
    }

	protected function maskToGroups()
	{
		$parts = explode($this->dot, $this->mask);
		$ret = [];
		foreach( $parts as $part) {
			$ret[] = strlen($part);
		}
		return $ret;
	}

	public function formatValue($value)
	{
		$mask_parts = explode($this->dot, $this->mask);
		$parts = explode($this->dot, $value);
		$ret = '';
		for( $i=0; $i<count($mask_parts); ++$i) {
			if( $ret != '' ) {
				$ret .= $this->dot;
			}
			$ret .= str_pad($parts[$i]??'0', strlen($mask_parts[$i]), '0', STR_PAD_LEFT);
		}
		return $ret;
	}

}
