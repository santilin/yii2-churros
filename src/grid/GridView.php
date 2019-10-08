<?php
/**
 * Just Another Grid Widget
 */
namespace santilin\churros\grid;

use Yii;
use yii\helpers\Html;
use kartik\grid\GridView as BaseGridView;
use santilin\churros\grid\GridGroup;

class GridView extends BaseGridView
{
	/**
	 * The group headers and footers definitions
	 */
	public $groups = [];
	/**
	 * The column to group
	 */
	public $column = null;

	protected $summaries = [];
	protected $last_groups = [];

	public function init()
	{
		$this->initGroups();
		parent::init();
		if( count($this->groups) != 0 ) {
			$this->beforeRow = function($model, $key, $index, $grid) {
				return $grid->groupHeader($model, $key, $index, $grid);
			};
		}
		$this->initSorting();
	}

	protected function initGroups()
	{
		foreach( $this->groups as $kg => $group_def ) {
            if (is_string($group_def)) {
                $group = $this->createGroup($group_def);
            } else {
                $group = Yii::createObject(array_merge([
                    'class' => GridGroup::className(),
                    'grid' => $this,
                ], $group_def));
            }
            if (!$group->visible) {
                unset($this->groups[$kg]);
                continue;
            }
            $this->groups[$kg] = $group;
            // Hide the group column
            if( $group->column ) {
  				$this->columns[$group->column]['visible'] = false;
			}
        }
	}

	/**
	 * Appends the groups orders to the default or current orders
	 */
	protected function initSorting()
	{
		$s = $this->dataProvider->getSort();
		$s->enableMultiSort = true;
		$def_order = $this->dataProvider->getSort()->getAttributeOrders(false);
		$new_def_order = [];
		$nc = 0;
		$def_order_columns = array_keys($def_order);
		foreach( $this->groups as $key => $group ) {
			if( $def_order_columns[$nc] == $group->column) {
				$new_def_order[$group->column] = $def_order[$group->column];
				$def_order[$group->column] = null;
			} else {
				$new_def_order = [ $group->column => SORT_ASC ];
			}
			++$nc;
		}
		foreach( $def_order as $key => $value ) {
			if( $value === null ) {
				unset($def_order[$key]);
			}
		}
		$new_def_order += $def_order;
		$s->setAttributeOrders($new_def_order);
	}

	protected function groupHeader($model, $key, $index, $grid)
	{
		$ret = '';
		$colspan = count($this->columns);
		foreach( $this->groups as $kg => $group ) {
			if( $group->updateHeader($model, $key, $index) ) {
				$ret .= "<tr><td colspan=\"$colspan\">" .
					$group->getHeaderContent($model, $key, $index)
					. "</td></tr>";
			}
		}
		return $ret;
	}


    /**
     * @inheritdoc
     * Redefined to show the column of the error in case of error
     */
    public function renderFilters()
    {
        if ($this->filterModel !== null) {
            $cells = [];
            foreach ($this->columns as $column) {
                /* @var $column Column */
                try {
					$cells[] = $column->renderFilterCell();
				} catch( \Exception $e ) {
					throw new \Exception($column->attribute . ": " . $e->getMessage());
				}
            }

            return Html::tag('tr', implode('', $cells), $this->filterRowOptions);
        }

        return '';
    }

}
