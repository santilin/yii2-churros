<?php
/**
 * A group for a grid
 */

namespace santilin\churros\grid;

use yii\base\BaseObject;


class GridGroup extends BaseObject
{
	/**
	 * The parent grid
	 */
	public $grid;
	
	/** 
	 * @var string The column we are grouping by
	 */
	public $column;
	
	public $summaryColumns;
	
	public $header;
	
	public $footer;
	
	public $visible = true;
	
	public $value;
	
	public $group_change = false;
	
	
	protected $first_value = true;
	protected $last_value = null, $current_value = null;
	protected $last_summary = null, $current_summary = null;
	
	public function getCurrentValue()
	{
		return $this->current_value;
	}
	
	public function updateHeader($model, $key, $index) 
	{
		if( $this->value instanceOf \Closure ) {
			$this->current_value = call_user_func($this->value, $model, $key, $index, $this->grid);
		}
		$this->group_change = ($this->last_value !== $this->current_value);
		$this->last_value = $this->current_value;
		return $this->group_change;
	}

	public function getHeaderContent($model, $key, $index) 
	{
		$hc = $this->header['content'];
		if( $hc instanceOf \Closure ) {
			return call_user_func($hc, $model, $key, $index, $this);
		} else {
			return "Undefined header content";
		}
	}
}
