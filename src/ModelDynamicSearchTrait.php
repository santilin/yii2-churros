<?php namespace santilin\churros;

use Yii;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\base\InvalidArgumentException;
use kartik\grid\GridView;

/**
 * Eases the definition of filters and sorts in grids for search models
 *
 * Creates the properties that hold the related filter values.
 * Extracts the sort and where properties from the related search model data provider
 * @todo Extract the rules from the related model
 */
trait ModelSearchTrait
{
	protected $related_properties = [];
	private $dynamic_rules = [];
	static public $operators = [
		'=' => '=',
		'===' => 'Exactamente igual', // Distinguish = (in grid filter) from === in search form
		'<>' => '<>',
		'START' => 'Comienza por', 'NOT START' => 'No comienza por',
		'LIKE' => 'Contiene', 'NOT LIKE' => 'No contiene',
		'>' => '>', '<' => '<',
		'>=' => '>=', '<=' => '<=',
		'SELECT' => 'Valor(es) de la lista',
		'BETWEEN' => 'entre dos valores', 'NOT BETWEEN' => 'no entre dos valores',
	];
	static public $extra_operators = [
		'BETWEEN', 'NOT BETWEEN'
	];

	/*
	 * Called when setting a filter in search()
	 * If the attribute is a related model field, set $this->related_properties and not $this->properties
	 */
    public function setAttributes($values, $safeOnly = true)
    {
		foreach( $values as $attribute => $value ) {
 			if( $attribute == '_adv_' ) {
				continue;
			}
			if (!array_key_exists($attribute, $this->attributes) ) {
				$this->related_properties[$attribute] = $value;
				unset($values[$attribute]);
			}
		}
		return parent::setAttributes($values, $safeOnly);
    }

    public function __get($name)
    {
		if( isset($this->related_properties[$name]) ) {
			return $this->related_properties[$name];
		} else {
			try {
				return parent::__get($name);
			} catch( \yii\base\UnknownPropertyException $e) {
				if( strpos($name, '.') !== FALSE ) {
					/// @todo findrelatedvalue
					return null;
 					return ArrayHelper::getValue($this, $name);
				} else {
					throw $e;
				}
			}
		}
	}

	/**
	 * Set the related properties values from params
	 */
    public function __set($name, $value)
    {
		try {
			return parent::__set($name, $value);
		} catch (\yii\base\UnknownPropertyException $e) {
			$this->related_properties[$name] = $value;
		}
	}

	/**
	 * Adds related sorts and filters to dataproviders
	*/
    public function addSafeRules($gridColumns)
    {
		foreach( $gridColumns as $attribute => $colum_def ) {
			if ( is_int($attribute) || array_key_exists($attribute, $this->attributes ) ) {
				continue;
			}
			$this->dynamic_rules[] = [[$attribute], 'safe'];
		}
    }


	/**
	 * Adds related sorts and filters to dataproviders for grids
	*/
    public function addColumnSortsToProvider($gridColumns, &$provider)
    {
		foreach( $gridColumns as $attribute => $column_def ) {
			if ( is_int($attribute)
				|| $attribute == '__actions__'
				|| array_key_exists($attribute, $this->attributes ) ) {
				continue;
			}
			$attribute = $column_def['attribute'];
			if( strpos($attribute, '.') === FALSE ) {
				$relation_name = $attribute;
				$sort_fldname = '';
			} else {
				list($relation_name, $sort_fldname) = ModelInfoTrait::splitFieldName($attribute);
			}
			if (isset(self::$relations[$relation_name]) ) {
				$related_model_class = self::$relations[$relation_name]['modelClass'];
				$table_alias = "as_$relation_name";
				// Activequery removes duplicate joins
				$provider->query->joinWith("$relation_name $table_alias");
				$provider->query->distinct(); // Evitar duplicidades debido a las relaciones hasmany
				if ($sort_fldname == '' ) { /// @todo junction tables
					$code_field = $related_model_class::findCodeField();
					$sort_fldname = $code_field;
				}
				if( $sort_fldname != '' && !isset($provider->sort->attributes[$attribute])) {
					$related_model_search_class = $related_model_class::getSearchClass();
					if( class_exists($related_model_search_class) ) {
						// Set orders from the related search model
						$related_model = new $related_model_search_class;
						$related_model_provider = $related_model->search([]);
						if (isset( $related_model_provider->sort->attributes[$sort_fldname]) ) {
							$related_sort = $related_model_provider->sort->attributes[$sort_fldname];
							$new_related_sort = [ 'label' => $related_sort['label']];
							unset($related_sort['label']);
							foreach( $related_sort as $asc_desc => $sort_def) {
								foreach( $sort_def as $key => $value ) {
									$new_related_sort[$asc_desc]
									= [ $table_alias.".".$key => $value ];
								}
							}
							$provider->sort->attributes[$attribute] = $new_related_sort ;
						}
					}
				}
			}
		}
    }

	public function transformGridFilterValues()
	{
		foreach( $this->attributes as $name => $value ) {
			if(is_array($value) ) {
				continue;
			}
			if( substr($value,0,2) == '{"' && substr($value,-2) == '"}' ) {
				$value = json_decode($value, true);
			}
			if( isset($value['lft']) ) {
				if( in_array($value['op'], ['=','<','>','<=','>=','<>'] ) ) {
					$this->$name = $value['op'] . $value['lft'];
				} else if( $value['op'] == 'LIKE' ) {
					$this->$name = $value['lft'];
				}
			}
		}
	}

	static public function toOpExpression($value, $strict)
	{
		if( isset($value['op']) ) {
			return $value;
		}
		if( is_string($value) && $value != '') {
			if( substr($value,0,2) == '{"' && substr($value,-2) == '"}' ) {
				return json_decode($value, true);
			} else if( preg_match('/^(=|<>|<|<=|>|>=)(.*)$/', $value, $matches) ) {
				return [ 'lft' => $matches[2], 'op' => $matches[1], 'rgt' => null ];
			}
		}
		return [ 'op' => $strict ? '=' : 'LIKE', 'lft' => $value, 'rgt' => '' ];
	}

	public function filterWhere(&$query, $fldname, $value)
	{
		$value = static::toOpExpression($value, false );
		if( $value['lft'] == null ) {
			return;
		}

		if( $fldname instanceof \yii\db\Expression ) {
			$this->addFieldFilterToQuery($query, $fldname, $value);
			return;
		}


		// addColumnSortsToProvider adds the join tables with AS `as_xxxxxxx`
		/// @todo add them as table1_table2_xxxxxx
		$tablename = $this->tableName();
		$fullfldname = null;
		if( strpos($fldname, '.') !== FALSE ) {
			$relmodel = $this->instance();
			while( strpos($fldname, '.') !== FALSE ) {
				list($relation, $fldname) = ModelInfoTrait::splitFieldName($fldname, false /*no reverse*/);
				if( isset($relmodel::$relations[$relation]) ) {
					$tablename = $relmodel::$relations[$relation]['relatedTablename'];
					$relmodel = $relmodel::$relations[$relation]['modelClass'];
				} else {
					$fullfldname = $fldname;
					break;
				}
			}
		}
		if( $fullfldname === null ) {
			$fullfldname = $tablename . "." . $fldname;
		}
		$this->addFieldFilterToQuery($query, $fullfldname, $value);
	}

	public function addFieldFilterToQuery(&$query, $fldname, array $value)
	{
		if( is_array($value['lft']) ) {
 			$query->andWhere([ 'in', $fldname, $value['lft']]);
		} else switch( $value['op'] ) {
			case "===":
			case "=":
				$query->andWhere([$fldname => $value['lft']]);
				break;
			case "<>":
			case ">=":
			case "<=":
			case ">":
			case "<":
			case "NOT LIKE":
			case "LIKE":
				$query->andWhere([ $value['op'], $fldname, $value['lft'] ]);
				break;
			case "START":
				$query->andWhere([ 'LIKE', $fldname, $value['lft'] . '%', false]);
				break;
			case "NOT START":
				$query->andWhere([ 'NOT LIKE', $fldname, $value['lft'] . '%', false]);
				break;
			case "BETWEEN":
			case "NOT BETWEEN":
				$query->andWhere([ $value['op'], $fldname, $value['lft'], $value['rgt'] ]);
				break;
		}
	}

	protected function filterWhereRelated(&$query, $name, $value)
	{
		if( $value === null || $value === '' ) {
			return;
		}
		if( strpos($name, '.') === FALSE ) {
			$relation_name = $name;
			$attribute = '';
		} else {
			list($relation_name, $attribute) = ModelInfoTrait::splitFieldName($name);
		}
		$relation = self::$relations[$relation_name]??null;
		if( $relation ) {
			$related_model_class = $relation['modelClass'];
			$filter_set = false;
			$table_alias = "as_$relation_name";
			// Activequery removes duplicate joins (added also in addSort)
			$query->joinWith("$relation_name $table_alias");
			$value = static::toOpExpression($value, false );
			if ($attribute == '' ) {
				if( isset($relation['other']) ) {
					list($right_table, $right_fld ) = ModelInfoTrait::splitFieldName($relation['other']);
				} else {
					list($right_table, $right_fld ) = ModelInfoTrait::splitFieldName($relation['right']);
				}
				$query->andFilterWhere([ 'IN', "$table_alias.$right_fld", $value['lft'] ]);
			} else {
				$query->andFilterWhere([$value['op'], "$table_alias.$attribute", $value['lft'] ]);
			}
		} else {
			throw new InvalidArgumentException($relation_name . ": relation not found in model " . self::class . '::$relations');
		}
	}

	/**
	 * Loads the advanced search form data
	 */
	public function load($params, $formName = null)
	{
		if( isset($params['_pjax']) ) {
			// filter form inside grid
			return parent::load($params, $formName);
		} else {
			// filter form outside grid
			// join search form params
			$ret = parent::load($params, $formName);
			if( $ret ) {
				$newparams = [];
                $scope = ($formName === null) ? $this->formName() : $formName;
				if( isset($params[$scope]['_adv_']) ) {
					foreach( $params[$scope]['_adv_'] as $name => $values) {
						if( isset($values['lft']) && $values['lft']!=='' && $values['lft']!==null ) {
							$newparams[$name] = $this->makeSearchParam($values);
						}
					}
					return parent::load([ $scope => $newparams], $scope);
				}
			}
			return $ret;
		}
	}

	/**
	 * Returns Html code to add an advanced search field to a search form
	 */
	public function createSearchField($attribute, $type = 'string', $options = [],
		$attribute_values = null )
	{
		$relation = '';
		if( ($dotpos = strrpos($attribute, '.')) !== FALSE ) {
			$relation = "&nbsp;(" . substr($attribute, 0, $dotpos) . ")";
		}
		unset($options['relation']);
		$attr_class = str_replace('.','_',$attribute);
		switch( $type ) {
		default:
			$control_type = 'text';
		}
		if ( (isset($options['hideme']) && $options['hideme'] == true)
			|| (isset($options['visible']) && $options['visible'] == false) ) {
			$main_div = ' class="row collapse hideme"';
		} else {
			$main_div = '';
		}
		unset($options['hideme']);
		$ret = '';
		$scope = $this->formName();
		if ($this->hasAttribute($attribute) || isset($this->related_properties[$attribute]) ) {
			$value = $this->$attribute;
		} else {
			$value = null;
		}
		$value = static::toOpExpression($value, false);
		if( !in_array($value['op'], ModelSearchTrait::$extra_operators) ) {
			$extra_visible = "display:none";
		} else {
			$extra_visible = '';
		}
		$ret .= "<div$main_div>";
		$ret .= "<div class='form-group'>";
		$ret .= "<div class='control-label col-sm-2'>";
		$ret .= Html::activeLabel($this, $attribute, $options['labelOptions']??[]) . $relation;
		if ($type == 'date' ) {
			$ret .= "<br>Formato yyyy-mm-dd";
		}
		$ret .= "</div>";

		$ret .= "<div class='control-form col-sm-2'>";
		$ret .= Html::dropDownList("${scope}[_adv_][$attribute][op]",
			$value['op'], ModelSearchTrait::$operators, [
			'id' => "drop-$attr_class", 'class' => 'search-dropdown form-control col-sm-2'] );
		$ret .= "</div>";

		if( is_array($attribute_values) || is_array($value['lft']) ) {
			$ret .= "<div class='control-form col-sm-5'>";
			$ret .= Html::dropDownList("${scope}[_adv_][$attribute][lft]",
				$value['lft'], $attribute_values,
				array_merge($options['htmlOptions']??[], [ 'prompt' => Yii::t('churros', 'Cualquiera')]));
			$ret .= "</div>";
		} else {
			$ret .= <<<EOF
	<div class="input-group col-sm-5">
EOF;

			$ret .= Html::input($control_type, "${scope}[_adv_][$attribute][lft]", $value['lft'],
				array_merge($options['htmlOptions']??[], [ 'class' => 'form-control' ]));
			$ret .= <<<EOF
    </div>
EOF;
		}
		$ret .= "</div><!-- row -->";

		$ret .= <<<EOF
	<div style="$extra_visible" id="second-field-drop-$attr_class">
<div class="row gap10">
		<div class='control-label col-sm-2'></div>
		<div class='control-form col-sm-2 text-right'>
y:
</div>
EOF;

		if( is_array($attribute_values) ) {
			$ret .= "<div class='control-form col-sm-5'>";
			$ret .= Html::dropDownList("${scope}[_adv_][$attribute][rgt]",
				$value['rgt'], $attribute_values,
				array_merge($options['htmlOptions']??[], [ 'prompt' => Yii::t('churros', 'Cualquiera')]));
		} else {
			$ret .= '<div class="input-group col-sm-5">';
			$ret .= Html::input($control_type, "${scope}[_adv_][$attribute][rgt]", $value['rgt'],
				array_merge($options['htmlOptions']??[], [ 'class' => 'form-control' ]));
			$ret .= <<<EOF
EOF;
		}
		$ret .= "</div>";
		$ret .= "</div><!-- row -->";
		$ret .= "</div>";

		$ret .= "</div>";
		return $ret;
	}

	public function createReportFilterField(array $dropdown_columns, ?string $attribute,
		array $value, string $type = 'string', array $options = [], $attribute_values = null)
	{
		$attr_class = str_replace('.','_',$attribute);
		switch( $type ) {
		default:
			$control_type = 'text';
		}
		$ret = '';
		$scope = $this->formName();
		if( empty($value) ) {
			$value = [ 'op' => 'LIKE', 'lft' => '', 'rgt' => '' ];
		}
		if( !in_array($value['op'], ModelSearchTrait::$extra_operators) ) {
			$extra_visible = "display:none";
		} else {
			$extra_visible = '';
		}
		$ret .= "<td>";
		$ret .= Html::dropDownList("{$scope}[attribute][]", $attribute,
		$dropdown_columns, [
			'class' => 'form-control',
			'prompt' => [
				'text' => 'Elige una columna', 'options' => ['value' => '', 'class' => 'prompt',
					'label' => 'Elige una columna']
			]
		]);
		$ret .= "</td>";

		$ret .= "<td class='control-form'>";
		$ret .= Html::dropDownList("${scope}[op][]",
			$value['op'], self::$operators, [
			'id' => "drop-$attr_class", 'class' => 'search-dropdown form-control',
			] );
		$ret .= "</td>";

		if( is_array($attribute_values) || is_array($value['lft']) ) {
			$ret .= "<td class='control-form'>";
			$ret .= Html::dropDownList("${scope}[lft][]",
				$value['lft'], $attribute_values,
				array_merge($options['htmlOptions']??[], [ 'prompt' => Yii::t('churros', 'Cualquiera')]));
			$ret .= "</td>";
		} else {
			$ret .= <<<EOF
	<td class="input-group">
EOF;
			$ret .= Html::input($control_type, "${scope}[lft][]", $value['lft'],
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
			$ret .= Html::dropDownList("${scope}[rgt][]",
				$value['rgt'], $attribute_values,
				array_merge($options['htmlOptions']??[], [ 'prompt' => Yii::t('churros', 'Cualquiera')]));
			$ret .= '</span>';
		} else {
			$ret .= '<span class="input-group">';
			$ret .= Html::input($control_type, "${scope}[rgt][]", $value['rgt'],
				array_merge($options['htmlOptions']??[], [ 'class' => 'form-control' ]));
			$ret .= <<<EOF
	</span>
EOF;
		}
		$ret .= "</td>";
		return $ret;
	}


} // class
