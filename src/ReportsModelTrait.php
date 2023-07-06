<?php
namespace santilin\churros;

use yii;
use yii\helpers\{Html,ArrayHelper};
use yii\db\Query;
use yii\data\ActiveDataProvider;
use santilin\churros\ModelSearchTrait;
use santilin\churros\widgets\grid\ReportView;
use santilin\churros\helpers\AppHelper;

trait ReportsModelTrait
{
	public $report_columns = [];
	public $report_filters = [];
	public $report_sorting = [];
	public $only_totals = false;
	public $landscape = false;
	protected $pre_summary_columns = [];

	public function getReportValue($var, $default)
	{
		$values = unserialize($this->value);
		if( isset($values->$var) ) {
			return $values->$var;
		} else {
			return $default;
		}
	}

	/**
	 * Loads the data from the form POST into the model
	 */
	public function load($data, $formName = null)
	{
		$scope = $formName === null ? $this->formName() : $formName;
		if( !parent::load($data, $formName) ) {
			return false;
		}
		if( isset($data[$scope]['only_totals'] ) ) {
			$this->only_totals = boolval($data[$scope]['only_totals']);
		}
		if( isset($data[$scope]['landscape'] ) ) {
			$this->landscape = boolval($data[$scope]['landscape']);
		}
		if( isset($data['report_columns']) ) {
			unset($data['report_columns']['_index_']);
			$this->report_columns = $data['report_columns'];
		}
		if( isset($data['report_filters']) ) {
			unset($data['report_filters']['_index_']);
			$this->report_filters = $data['report_filters'];
		}
		if( isset($data['report_sorting']) ) {
			unset($data['report_sorting']['_index_']);
			$this->report_sorting = [];
			foreach ($data['report_sorting'] as $rs) {
				unset($rs['group_check'], $rs['show_header_check'], $rs['show_footer_check'],
					$rs['show_column_check']);
				if (empty($rs['group'])) {
					$rs['show_header'] = 0;
					$rs['show_footer'] = 0;
					$rs['show_column'] = 1;
				}
				$this->report_sorting[] = $rs;
			}
		}
		return true;
	}

	public function encodeValue()
	{
		$value = json_decode($this->value, false);
		if( $value === null ) {
			$value = new \stdClass;
		}
		$value->report_columns = $this->report_columns;
		$value->report_filters = $this->report_filters;
		$value->report_sorting = $this->report_sorting;
		$value->only_totals = $this->only_totals;
		$value->landscape= $this->landscape;
		$this->value = json_encode($value);
	}

	public function decodeValue()
	{
		$value = json_decode($this->value, true);
		$this->report_columns = $value['report_columns']??[];
		$this->report_filters = $value['report_filters']??[];
		$this->report_sorting = $value['report_sorting']??[];
		$this->only_totals = $value['only_totals']??false;
		$this->landscape= $value['landscape']??false;
	}

	protected function findColumn($attribute)
	{
		foreach( $this->report_columns as $rk => $coldef ) {
			if( $coldef['attribute'] == $attribute ) {
				return $coldef;
			}
		}
	}

	private static function removeMainTablename(string $selectExpression, string $tableName): string
	{
		// Create a regular expression pattern to match the table name preceded by a dot
		$pattern = '/(\[)?(\{)?' . preg_quote($tableName, '/') . '(\})?(\])?\./i';
		$modifiedExpression = preg_replace($pattern, '', $selectExpression);
		return $modifiedExpression;
	}

	/**
	 * Transforms grid columns into report columns
	 */
	public function fixColumnDefinitions($model, array $allColumns): array
	{
		$columns = [];
		$tablename = str_replace(['{','}','%'], '', $model->tableName());
		foreach( $allColumns as $colname => $column ) {
			if (!isset($column['attribute']) ) {
				$column['attribute'] = $colname;
			}
			if( !isset($column['contentOptions']) ) {
				$column['contentOptions'] = [];
			}
			if( !isset($column['headerOptions']) ) {
				$column['headerOptions'] = [];
			}
			if( !isset($column['footerOptions']) ) {
				$column['footerOptions'] = [];
			}
			$classes = explode(' ', $column['options']['class']??'');
			if( isset($column['format']) ) {
				if( is_array($column['format']) ) {
					$classes[] = 'reportview-' . $column['format'][0];
				} else {
					$classes[] = 'reportview-' . $column['format'];
				}
			}
			$column['contentOptions']['class'] = $column['headerOptions']['class']
				= $column['footerOptions']['class'] = trim(implode(' ', $classes));
			if( preg_match('/^(sum|avg|max|min):(.*)$/i', $colname, $matches) ) {
				if( empty($column['attribute']) ) {
					$column['attribute'] = $matches[2];
				}
				$column['pre_summary'] = $matches[1];
			} else {
				if( empty($column['attribute']) ) {
					$column['attribute'] = $colname;
				}
				$column['pageSummaryFunc'] = $column['summary']??'';
			}
			unset($column['summary']);
			if( empty($column['label']) ) {
				$a = $colname;
				if( ($dotpos=strpos($a, '.')) !== FALSE ) {
					$t = substr($a, 0, $dotpos);
					if( $t == $tablename ) {
						$a = substr($a, $dotpos+1);
					}
				}
				$column['label'] = $model->getAttributeLabel($a);
			}
			$columns[$colname] = $column;
		}
		return $columns;
	}

	/**
	 * Creates the data provider for the report grid.
	 * Sets the query, select, joins, orderBy, groupBy and filters
	 * @param ModelInfoTrait $model the data model for the report
	 * @param array $columns the columns included in the report definition
	 * @param array $all_columns all the columns available for the rerpot definition
	 */
	public function dataProviderForReport($model, array &$columns, array $all_columns)
    {
        $query = new Query(); // Do not use $model->find(), as we want a query, not active records

        $provider = new ActiveDataProvider([
            'query' => $query->from($model->tableName()),
            'pagination' => [
				'pagesize' => $_GET['per-page']??10,
				'page' => $_GET['page']??0,
			],
		]);

		$selects = [];
		$joins = [];
		$groups = [];
		$orderby = [];
		$tablename = str_replace(['{','}','%'], '', $model->tableName() );

		// Añadir join y orderby de los report_sorting
		foreach( $this->report_sorting as $sorting_def ) {
			$colname = $sorting_def['name'];
			if( !isset($all_columns[$colname]) ) {
				Yii::$app->session->addFlash("error", "Report '" . $this->name . "': column '$colname' not found @dataProviderForReport");
				continue;
			}
			$column_def = $all_columns[$colname];
			list($select_field_alias, $select_field) = $this->aliasesAndJoins($model,
				$tablename, $column_def['attribute'], $colname, $joins);
			$selects[$select_field_alias] = $select_field;
			$orderby[] = $select_field_alias
					. ($sorting_def['asc']??SORT_ASC==SORT_ASC?' ASC':' DESC');
		}
		$provider->query->orderBy( join(',',$orderby) );
		$provider->sort = false;


		// Añadir join y where de los report_filters
		foreach( $this->report_filters as $filter_def ) {
			$colname = $filter_def['name'];
			if( !isset($all_columns[$colname]) ) {
				Yii::$app->session->addFlash("error", "Report '" . $this->name . "': column '$colname' of filter not found");
				continue;
			}
			$column_def = $all_columns[$colname];
			list($select_field_alias, $select_field) = $this->aliasesAndJoins($model,
				$tablename, $column_def['attribute'], $colname, $joins);
			$model->filterWhere($query, $select_field, $filter_def);
		}

		// Añadir join y from de los report_columns
		foreach( $columns as $kc => $column_def ) {
			$colname = $column_def['name'];
			if( !isset($column_def['name']) ) {
 				Yii::$app->session->addFlash("error", "Report '" . $this->name . "': column '$kc' has no name in addSelectToQuery");
 				continue;
			}
			list($select_field_alias, $select_field) = $this->aliasesAndJoins($model,
				$tablename, $column_def['attribute'], $colname, $joins);
			$selects[$select_field_alias] = $select_field;
 			// group by solo de los pre_summary, los otros grupos los maneja ReportView
 			if (count($this->pre_summary_columns)
				&& !in_array($column_def['name'], $this->pre_summary_columns) ) {
				$groups[] = $select_field_alias;
			}
 			$columns[$kc]['attribute'] = $select_field_alias;
		}

		$query->select($selects);
		foreach( $joins as $jk => $jv ) {
			$related_table  = array_shift($jv);
			if ($jk != $related_table) {
				$query->innerJoin([$jk=>$related_table], $jv[0]);
			} else {
				$query->innerJoin($jk, $jv[0]);
			}
		}
		$query->groupBy($groups);
		return $provider;
    }

	/* Añade a $joins todas las joins necesarias para este campo relacionado, multinivel */
	protected function aliasesAndJoins($left_model, string $tablename,
		string $attribute, string $colname, array &$joins): array
	{
		if( ($dotpos = strpos($colname, '.')) !== FALSE ) {
			$table_alias = $final_table = '';
			$inner_relations = 0;
			while( ($dotpos = strpos($colname, '.')) !== FALSE ) {
				++$inner_relations;
				$relation_name = substr($colname, 0, $dotpos);
				$colname = substr($colname, $dotpos + 1);
				if( empty($table_alias) ) {
					if($relation_name == $tablename) {
						$table_alias = $tablename;
						$final_table = $tablename;
						continue;
					}
				} else {
					$table_alias .= "_";
					$final_table .= '.';
				}
				$table_alias .= $relation_name;
				$final_table .= $relation_name;
				if( isset($left_model::$relations[$relation_name]) ) {
					$relation = $left_model::$relations[$relation_name];
					if( !isset($joins[$table_alias]) ) {
						$relation_table = $relation['relatedTablename'];
						if ($table_alias != $relation_table) {
							$on_expression = str_replace($relation_table.'.', $table_alias.'.', $relation['join']);
							if (count($joins) > 0 ) {
								$join_alias = array_keys($joins)[count($joins)-1];
								$join_table = $joins[$join_alias][0];
								$on_expression = str_replace("$join_table.", "$join_alias.", $on_expression);
							}
							$joins[$table_alias] = [ $relation_table, $on_expression];

						} else {
							$joins[$table_alias] = [ $relation_table, $relation['join']];
						}
					}
					$left_model = $relation['modelClass']::instance();
				} else {
					throw new \Exception($relation_name . ": relation not found in model " . $left_model::className() . " with relations " . join(',', array_keys($left_model::$relations)));
				}
			}
			if ($colname != $attribute) {
				$aliased_attribute = $attribute;
			} else {
				$aliased_attribute = $table_alias . "_$attribute";
			}
			if ($inner_relations>1) {
				$attribute = static::removeMainTablename($attribute, $tablename);
				$final_table = static::removeMainTablename($final_table, $tablename);
			}
			$attribute = str_replace("$final_table.", "$table_alias.", $attribute);
		} else {
			$aliased_attribute = $attribute;
		}
		if (strpos($attribute, '(')!==FALSE) {
			$attribute = new \yii\db\Expression($attribute);
		}
		$aliased_attribute = strtr($aliased_attribute, [
			'.' => '_', ',' => '_',
			'(' => '_', ')' => '_',
			"'" => '_', '"' => '_',
			' ' => '',
		]);
		return [ $aliased_attribute, $attribute ];
	}


	/**
	 * Gets only the columns used in this report
	 */
	public function extractReportColumns($allColumns)
	{
		$columns = [];
		foreach( $this->report_columns as $column_def ) {
			$colname = $column_def['name'];
			if( !isset($allColumns[$colname]) ) {
 				Yii::$app->session->addFlash("error", "Report '" . $this->name . "': column '$colname' does not exist in report_columns @extractReportColumns");
 				continue;
			}
			$column_def_format = ArrayHelper::getValue($column_def, 'format', 'raw');
			$all_columns_format = $allColumns[$colname]['format'][0]??false;
			if ( is_array($all_columns_format) && $column_def_format == $all_columns_format) {
				// format => [ 'label', $label_values ]
				$column_def['format'] = $allColumns[$colname]['format'];
			}
			$column_to_add = ArrayHelper::merge($allColumns[$colname], array_filter($column_def));
			if( !isset($column_to_add['attribute']) ) {
 				$column_to_add['attribute'] = $colname;
			}
			if( !empty($column_to_add['pre_summary']) ) {
				$this->pre_summary_columns[] = $colname;
				$pre_sum_func = substr($column_to_add['pre_summary'],2);
				$column_to_add['attribute'] = "$pre_sum_func({$column_to_add['attribute']})";
			}
			unset($column_to_add['pre_summary']);
			if( isset($column_to_add['summary']) ) {
				$column_to_add['pageSummaryFunc'] = $column_to_add['summary'];
				unset($column_to_add['summary']);
			}
			$columns[$colname] = $column_to_add;
		}
		return $columns;
	}


	public function reportGroups(array &$report_columns, array $all_columns): array
	{
		$groups = [];
		foreach( $this->report_sorting as $column ) {
			if( !empty($column['group']) ) {
				$colname = $column['name'];
				if( isset($report_columns[$colname]) ) {
					$rc = $report_columns[$colname];
					$groups[$colname] = [
						'column' => $rc['attribute'],
						'format' => !empty($rc['label']) ? ($rc['label'] . ': {group_value}') : '{group_value}',
						'header' => $column['show_header']??true,
						'footer' => $column['show_footer']??true,
					];
				} else if( isset($all_columns[$colname]) ) {
					$rc = $all_columns[$colname];
					$groups[$colname] = [
						'column' => $rc['attribute'],
						'format' => !empty($rc['label']) ? ($rc['label'] . ': {group_value}') : '{group_value}',
						'header' => $column['show_header']??true,
						'footer' => $column['show_footer']??true,
					];
				} else {
					Yii::$app->session->addFlash("error", "Report '" . $this->name . "': group column '$colname' not found @reportGroups");
					continue;
				}
				if( empty($column['show_column']) ) {
					$report_columns[$colname]['visible'] = false;
				}
			}
		}
		return $groups;
	}

	/**
	 * Groups the available report columns by table and returns an array for a dropDownList
	 */
	public function columnsForDropDown($model, $columns, $titles)
	{
 		$dropdown_options = [];
		$modeltablename = str_replace(['{','}','%'], '', $model->tableName());
		foreach( $columns as $colname => $colattrs ) {
			list($tablename, $fieldname) = ModelInfoTrait::splitFieldName($colname);
			if( empty($tablename) ) {
				$tablename = $modeltablename;
			}
			if ($tablename != $modeltablename) {
				$love = true;
			}
			$group = $titles[$tablename]??$tablename;
			$attr = $colattrs['attribute']??null;
			$title = '';
			if( substr($colname, -11) == '.desc_short' ) {
				$title = ' (descripción)';
			} else if( substr($colname, -10) == '.desc_long' ) {
				$title = ' (descripción larga)';
// 			} else {
// 				if ($modeltablename != $tablename) {
// 					$title = " ($group)";
// 				} else {
// 					$title = '';
// 				}
			}
			$dropdown_options[$group][$colname] = $colattrs['label'] . $title;
		}
		return $dropdown_options;
	}


	public function createReportFilterField($index, array $dropdown_columns, ?string $attribute,
		array $value, string $type = 'string', array $options = [], $attribute_values = null)
	{
		$attr_class = str_replace('.','_',$attribute);
		switch( $type ) {
		default:
			$control_type = 'text';
		}
		$ret = '';
		if( empty($value) ) {
			$value = [ 'op' => 'LIKE', 'lft' => '', 'rgt' => '' ];
		}
		if( !in_array($value['op'], ModelSearchTrait::$extra_operators) ) {
			$extra_visible = "display:none";
		} else {
			$extra_visible = '';
		}
		$ret .= "<td class=control-form>";
		$ret .= Html::dropDownList("report_filters[$index][name]", $attribute,
		$dropdown_columns, [
			'class' => 'form-control',
			'prompt' => [
				'text' => 'Elige una columna', 'options' => ['value' => '', 'class' => 'prompt',
					'label' => 'Elige una columna']
			]
		]);
		$ret .= "</td>";

		$ret .= "<td class=control-form>";
		$ret .= Html::dropDownList("report_filters[$index][op]",
			$value['op'], ModelSearchTrait::$operators, [
			'id' => "drop-$attr_class", 'class' => 'search-dropdown form-control',
			] );
		$ret .= "</td>";

		if( is_array($attribute_values) || is_array($value['lft']) ) {
			$ret .= "<td class='control-form'>";
			$ret .= Html::dropDownList("report_filters[$index][lft]",
				$value['lft'], $attribute_values,
				array_merge($options['htmlOptions']??[], [ 'prompt' => Yii::t('churros', 'Cualquiera')]));
			$ret .= "</td>";
		} else {
			$ret .= <<<EOF
	<td class="input-group">
EOF;
			$ret .= Html::input($control_type, "report_filters[$index][lft]", $value['lft'],
				array_merge($options['htmlOptions']??[], [ 'class' => 'form-control' ]));
			$ret .= <<<EOF
    </td>
EOF;
		}
		$ret .= <<<EOF
	<td style="$extra_visible" id="second-field-drop-$attr_class">
y:
EOF;

		if( is_array($attribute_values) ) {
			$ret .= "<span class='control-form'>";
			$ret .= Html::dropDownList("report_filters[$index][rgt]",
				$value['rgt'], $attribute_values,
				array_merge($options['htmlOptions']??[], [ 'prompt' => Yii::t('churros', 'Cualquiera')]));
			$ret .= '</span>';
		} else {
			$ret .= '<span class="input-group">';
			$ret .= Html::input($control_type, "report_filters[$index][rgt]", $value['rgt'],
				array_merge($options['htmlOptions']??[], [ 'class' => 'form-control' ]));
			$ret .= <<<EOF
	</span>
EOF;
		}
		$ret .= "</td>";
		return $ret;
	}

}
