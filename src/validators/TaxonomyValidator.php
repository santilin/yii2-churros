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
class TaxonomyValidator extends Validator
{
	public $taxonomy;


    public function init()
    {
        parent::init();
		if( empty($this->taxonomy) ) {
			throw new InvalidConfigException("The taxonomy must be set");
		}
		if( empty($this->taxonomy['mask']) ) {
			throw new InvalidConfigException("The taxonomy mask must be set");
		}
		if( empty($this->taxonomy['dot']) ) {
			$this->taxonomy['dot'] = '.';
		}
		$mask_groups = $this->maskToGroups($this->taxonomy['mask'], $this->taxonomy['dot']);
		if( count($this->taxonomy['levels']) > count($mask_groups)   ) {
			throw new InvalidConfigException("The number of levels can't be greater than the number of mask groups");
		}

        if ($this->message === null) {
             $this->message = Yii::t('churros', "{attribute} '{value}' does not match the mask {mask}.",
				[ 'mask' => $this->taxonomy['mask']]);
        }
    }

    public function validateAttribute($model, $attribute)
    {
		$value = $model->{$attribute};
		if( ($message = $this->validateValue($value)) === null ) {
			$model->{$attribute} = $this->formatValue($this->taxonomy['mask'], $this->taxonomy['dot'],$value);
		} else {
			$this->addError($model, $attribute, $message);
		}
	}

    /**
     * {@inheritdoc}
     */
    protected function validateValue($value)
    {
		$mask = trim($this->taxonomy['mask']);
		$dot = $this->taxonomy['dot'];
		if( $mask != '' ) {
			$mask_groups = $this->maskToGroups($mask, $dot);
			$regexp_dot = $dot;
			if( $dot == '.' ) {
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
			if( !preg_match('/^(' . $re_str . ')$/', $value ) ) {
				return $this->message;
			}
		}
		// Check taxonomy values
		$input_values = explode($dot, $value);
		$ng = min(count($mask_groups), count($this->taxonomy['levels']));
		for( $l=0; $l< $ng; ++$l) {
			$value = $input_values[$l];
			$taxon_values = $this->getTaxonomyValues($input_values, $l);
			if( !isset($taxon_values[$value]) && $value != 0) {
				return Yii::t('churros', 'The value \'{0}\' does not exist in the category \'{1}\'',
					[ $value, $this->taxonomy['levels'][$l]['title']] );
			}
		}
		return null;
    }

	protected function maskToGroups($mask, $dot)
	{
		$parts = explode($dot, $mask);
		$ret = [];
		foreach( $parts as $part) {
			$ret[] = strlen($part);
		}
		return $ret;
	}

	protected function formatValue($mask, $dot, $value)
	{
		$mask_parts = explode($dot, $mask);
		$parts = explode($dot, $value);
		$ret = '';
		for( $i=0; $i<count($mask_parts); ++$i) {
			if( $ret != '' ) {
				$ret .= $dot;
			}
			if( substr($mask_parts[$i],0,1) == '0' ) {
				$ret .= str_pad($parts[$i]??'1', strlen($mask_parts[$i]), '0', STR_PAD_LEFT);
			} else {
				$ret .= $parts[$i]??'1';
			}
		}
		return $ret;
	}

	protected function getTaxonomyValues($input_values, $level)
	{
		$items = $this->taxonomy['items'];
		// find the options for input[level]
		for( $l=0; $l<$level; ++$l ) {
			if( !isset($input_values[$l]) ) {
				break;
			}
			if( empty($items[$input_values[$l]])) {
				return [];
			}
			if( !isset($items[$input_values[$l]]['items']) ) {
				return [];
			}
			$items = $items[$input_values[$l]]['items'];
		}
		return $items;
	}


}
