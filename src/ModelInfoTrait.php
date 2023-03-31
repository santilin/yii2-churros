<?php namespace santilin\churros;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use santilin\churros\helpers\{YADTC,AppHelper};
use santilin\churros\ModelSearchTrait;

trait ModelInfoTrait
{
	use RelationTrait;
    /**
     * @var array validation warnings (attribute name => array of warnings)
     */
    private $_warnings;


    static public function empty($value)
    {
		return empty($value);
	}

	static public function bareTableName()
	{
		return strtr( self::tableName(), [ '{' => '', '}' => '', '%' => '' ] );
	}

	public function t($category, $message, $params = [], $language = null )
	{
		$translated = Yii::t($category, $message, $params, $language);
		if( ($language == null || $language == 'es') ) {
			$male_words = AppHelper::SPANISH_MALE_WORDS;
		} else {
			$male_words = [];
		}
		$matches = $placeholders = [];
		$female = $this->getModelInfo('female');
		if( preg_match_all('/({([a-zA-Z0-9\._]+)})+/', $translated, $matches) ) {
			foreach( $matches[2] as $match ) {
				$bracket_match = '{'.$match.'}';
				if( substr($match,0,6) == 'model.' ) {
					$fld = substr($match, 6);
					$placeholders[$bracket_match] = ArrayHelper::getValue($this,$fld,'');
				} else switch( $match ) {
				case 'title':
					$placeholders[$bracket_match] = lcfirst(static::getModelInfo('title'));
					break;
				case 'title_plural':
					$placeholders[$bracket_match] = lcfirst(static::getModelInfo('title_plural'));
					break;
				case 'Title':
					$placeholders[$bracket_match] = ucfirst(static::getModelInfo('title'));
					break;
				case 'Title_plural':
					$placeholders[$bracket_match] = ucfirst(static::getModelInfo('title_plural'));
					break;
				case 'record':
					$placeholders[$bracket_match] = $this->recordDesc();
					break;
				case 'record_link':
					$placeholders[$bracket_match] = $this->recordDesc('link');
					break;
				case 'record_long':
					$placeholders[$bracket_match] = $this->recordDesc('long');
					break;
				case 'record_medium':
					$placeholders[$bracket_match] = $this->recordDesc('medium');
					break;
				case 'record_short':
					$placeholders[$bracket_match] = $this->recordDesc('short');
					break;
				default:
					if( isset($male_words[$match]) ) {
						if( $female )  {
							$placeholders[$bracket_match] = $match;
						} else {
							$placeholders[$bracket_match] = $male_words[$match];
						}
					}
				}
			}
		}
		return strtr($translated, $placeholders);
	}

	public function recordDesc($format=null, $max_len = 0)
	{
		$ret = '';
		if( $format == null || $format == 'short' ) {
			$_format = self::getModelInfo('record_desc_format_short');
		} elseif( $format == 'long' ) {
			$_format = self::getModelInfo('record_desc_format_long');
		} elseif( $format == 'medium' ) {
			$_format = self::getModelInfo('record_desc_format_medium');
		} elseif( $format == 'code&desc' ) {
			$fields = static::findCodeAndDescFields();
			$_format = '{' . implode('}, {',array_filter($fields)) . '}';
		} else {
			$_format = $format;
		}
		$values = $matches = [];
		if( preg_match_all('/{([a-zA-Z0-9\._]+)(\%([^}])*)*}+/', $_format, $matches) ) {
			foreach( $matches[0] as $n => $match ) {
				$value = ArrayHelper::getValue($this, $matches[1][$n]);
				if( is_object($value) && method_exists($value, 'recordDesc') ) {
					$value = $value->recordDesc($format, $max_len);
				}
				$sprintf_part = $matches[2][$n];
				if( $sprintf_part == '' ) {
					$sprintf_part = "%s";
				} else if( $sprintf_part == '%T' ) {
					$sprintf_part = '%s';
					$value = Yii::$app->formatter->asDateTime($value);
				} else if( $sprintf_part == '%D' ) {
					$sprintf_part = '%s';
					$value = Yii::$app->formatter->asDate($value);
				}
				$_format = str_replace($match, $sprintf_part, $_format);
				$values[] = $value;
			}
			$ret = sprintf($_format, ...$values);
		} else {
			$ret = $_format;
		}
		if( $max_len == 0 ) {
			return $ret;
		} else if ($max_len < 0 ) {
			return substr($ret, 0, -$max_len);
		} else {
			$len = strlen($ret);
			if( $len > $max_len ) {
				$ret = mb_substr($ret, 0, ($max_len/2)-2) . '...' . mb_substr($ret, -($max_len/2)+2);
			}
		}
		return $ret;
	}

	public function linkToMe($base_route = '', $action = 'view')
	{
		$link = self::getModelInfo('controller_name') . "/$action/" . $this->getPrimaryKey();
		return $base_route . $link;
	}

	public function linkTo($action, $prefix = '', $format = 'short', $max_len = 0)
	{
		$url = $prefix;
		if ($url != '') {
			$url .= "/";
		}
		$url .= $this->controllerName();
		if( $this->getIsNewRecord() ) {
			$url .= '/create';
			return \yii\helpers\Html::a($this->t("Create {title}"), $url);
		} else {
			$url .= "/$action";
			return \yii\helpers\Html::a($this->recordDesc($format, $max_len),
					[$url, 'id' => $this->getPrimaryKey() ]);
		}
	}

	public function increment($fldname, $increment, $conds = [], $usegaps = true)
	{
		if( $increment == '' ) {
			$increment = "+1";
		} else if( $increment[0] != '+' ) {
			$increment = "+$increment";
		}
		$tablename = $this->tableName();
		if( $usegaps ) {
			$sql = "SELECT [[$fldname]]";
		} else {
			$sql = "SELECT MAX([[$fldname]])";
		}
		$sql .= " FROM $tablename";
		if( $usegaps || $conds != [] ) {
			$sql .= " WHERE ";
		}
		if( $conds != [] ) {
			$sql .= "(" . join(" AND ", $conds) . ")";
		}
		if( $usegaps ) {
			if( $conds != [] ) {
				$sql .= " AND ";
			}
			$sql .= "(CAST([[$fldname]] AS SIGNED)$increment NOT IN (SELECT [[$fldname]] FROM $tablename";
			if( $conds != [] ) {
				$sql .= " WHERE (" . join(" AND ", $conds) . ")";
			}
			$sql .= ") )";
		}
//     try {
		$val = Yii::$app->db->createCommand( $sql )->queryScalar();
        $fval =  AppHelper::incrStr($val, $increment);
        return $fval;
//     } catch( dbError &e ) {
//         if( e.getNumber() == 1137 ) { // ERROR 1137 (HY000): Can't reopen table:
//             sql = "SELECT MAX(" + nameToSQL( fldname ) + ")";
//             sql+= " FROM " + nameToSQL( tablename );
//             if( !conds.isEmpty() )
//                 sql+=" WHERE (" + conds + ")";
//             return selectInt( sql ) + 1;
//         } else throw;
//     }
    }

	public function setDefaultValues(bool $duplicating = false)
	{
	}

	public function saveOrFail(bool $runValidations = true)
	{
		if( !$this->save($runValidations) ) {
			throw new \Exception("Save " . static::getModelInfo('title') . ': ' . print_r($this->getErrors(), true) );
		}
	}

	static public function createFromDefault($number = 1)
    {
		$ret = [];
		for( $count = 0; $count < $number; ++$count ) {
			$modelname = get_called_class();
			$model = new $modelname;
			$model->setDefaultValues();
			if( $number == 1 ) {
				return $model;
			} else {
				$ret[] = $model;
			}
		}
		return $ret;
    }

    public function controllerName($prefix = '')
    {
		$c = self::getModelInfo('controller_name');
		if( !$c ) {
			$c = AppHelper::stripNamespaceFromClassName($this);
			$c = lcfirst(str_replace("Search", "", $c));
		}
		return $prefix . $c;
    }

    public function viewPath($prefix = '')
    {
		$c = AppHelper::stripNamespaceFromClassName($this);
		$c = lcfirst(str_replace("Search", "", $c));
		return "$prefix$c/";
    }

    public function getRelatedFieldForModel($related_model)
    {
		foreach( self::$relations as $relname => $rel_info ) {
			$cn = $related_model->className();
			if( $rel_info['modelClass'] == $cn ) {
				$related_field = $rel_info['left'];
				list($table, $field) = static::splitFieldName($related_field);
				return $field;
			}
		}
		// If it's a derived class like *Form, *Search, look up its parent
		foreach( self::$relations as $relname => $rel_info ) {
			$cn = get_parent_class($related_model);
			if( $rel_info['modelClass'] == $cn ) {
				$related_field = $rel_info['left'];
				list($table, $field) = static::splitFieldName($related_field);
				return $field;
			}
		}
		throw new \Exception( self::className() . ": not related to " . $related_model->className() );
    }

    public function getRelatedModelClass($relation_name)
    {
		if( isset(self::$relations[$relation_name]) ) {
			$rel_info = $this->relations[$relation_name];
			return $rel_info['model'];
		} else {
			throw new \Exception( self::className() . ": not related to " . $related_model->className() );
		}
	}

	public function addErrorsFrom(ActiveRecord $model, $key = null)
	{
		if( $key === null ) {
			$key = static::bareTableName() . '_';
		}
		foreach( $model->getErrors() as $k => $error ) {
			foreach( $error as $err_msg ) {
				$this->addError(  $key . $k, $err_msg);
			}
		}
	}

	public function addErrorFromException(\Throwable $e)
	{
		if( YII_ENV_DEV ) {
			$this->addError('record', $e->getMessage());
		} else {
			$this->addError('record', 'Para mantener la integridad de la base de datos, no se han guardado los datos.');
		}
	}

	public function getOneError():string
	{
		$errors = $this->getFirstErrors(false);
		if( count($errors) ) {
			return reset($errors);
		} else {
			return '';
		}
	}

	/**
	 * Returns at least one field that can be used as a code for this model
	 */
	static public function findCodeField()
	{
		$fields = explode(',',static::getModelInfo('code_field'))
			+ explode(',',static::getModelInfo('desc_field'));
		if( count($fields) ) {
			return array_pop($fields);
		} else {
			return [ $this->getPrimaryKey(), '' ];
		}
	}

	static public function findCodeAndDescFields(string $relname = null): array
	{
		if( $relname == null ) {
			$r0 = array_filter(explode(',',static::getModelInfo('code_field')));
			$r1 = array_filter(explode(',',static::getModelInfo('desc_field')));
			return array_merge($r0,$r1);
		} else if (isset(static::$relations[$relname])) {
			$relmodelname = static::$relations[$relname]['modelClass'];
			$relmodel = $relmodelname::instance();
			return $relmodel::findCodeAndDescFields();
		} else {
			return [];
		}
	}

	// No se usa
	public function IAmOwner()
	{
		$blameable = $this->getBehavior('blameable');
		if( $blameable ) {
			$created_by = $blameable->createdByAttribute;
			$author = $this->$created_by;
			return $author == Yii::$app->user->getIdentity()->id;
		} else {
			return false;
		}
	}

	public function checkAccessByRole(string $fldname): bool
	{
		if( trim($this->$fldname) == '' || AppHelper::userIsAdmin() ) {
			return true;
		}
		$perms = explode(',:;|',$this->$fldname);
		foreach($perms as $perm)  {
			if( \Yii::$app->user->can($perm) ) {
				return true;
			}
		}
		return false;
	}

	public function defaultHandyFieldValues($field, $format, $scope=null)
	{
		throw new \Exception("field '$field' not supported in " . get_called_class() . "::handyFieldValues() ");
	}

	public function formatHandyFieldValues($field, $values, $format)
	{
		if( $format == 'selectize' ) {
			$ret = [];
			foreach( $values as $k => $v ) {
				$ret[] = [ 'value' => $k, 'text' => $v ];
			}
			return $ret;
		} else if( $format == 'ids' ) {
			return array_keys($values);
		} else if( $format == 'values' ) {
			return array_values($values);
		} else if( $format == 'value' ) {
			return $values[$this->$field]??null;
		} else {
			return $values;
		}
	}

	static public function splitFieldName($fieldname, $reverse = true)
	{
		if( $reverse ) {
			$dotpos = strrpos($fieldname, '.');
		} else {
			$dotpos = strpos($fieldname, '.');
		}
		if( $dotpos !== FALSE ) {
			$fldname = substr($fieldname, $dotpos + 1);
			$tablename = substr($fieldname, 0, $dotpos);
			return [ $tablename, $fldname ];
		} else {
			return [ "", $fieldname ];
		}
	}

	public function asDate($fldname): ?YADTC
	{
		return YADTC::fromSql( $this->$fldname );
	}

	/**
	 * Code copied from ActiveRecord::findByCondition.
	 * Always Adds the tablename to the primary key field
	 */
	static public function byPrimaryKey($condition)
	{
        $query = static::find();

        if (!ArrayHelper::isAssociative($condition) && !$condition instanceof ExpressionInterface) {
            // query by primary key
            $primaryKey = static::primaryKey();
            if (isset($primaryKey[0])) {
                $pk = $primaryKey[0];
				$pk = static::tableName() . '.' . $pk;
                // if condition is scalar, search for a single primary key, if it is array, search for multiple primary key values
                $condition = [$pk => is_array($condition) ? array_values($condition) : $condition];
            } else {
                throw new InvalidConfigException('"' . get_called_class() . '" must have a primary key.');
            }
        } elseif (is_array($condition)) {
            $aliases = static::filterValidAliases($query);
            $condition = static::filterCondition($condition, $aliases);
        }

        return $query->andWhere($condition);
	}

    /**
     * Returns a value indicating whether there is any validation warning.
     * @param string|null $attribute attribute name. Use null to check all attributes.
     * @return bool whether there is any warning.
     */
    public function hasWarnings($attribute = null)
    {
        return $attribute === null ? !empty($this->_warnings) : isset($this->_warnings[$attribute]);
    }

    /**
     * Returns the warnings for all attributes or a single attribute.
     * @param string|null $attribute attribute name. Use null to retrieve warnings for all attributes.
     * @return array warnings for all attributes or the specified attribute. Empty array is returned if no warning.
     * See [[getWarnings()]] for detailed description.
     * Note that when returning warnings for all attributes, the result is a two-dimensional array, like the following:
     *
     * ```php
     * [
     *     'username' => [
     *         'Username is required.',
     *         'Username must contain only word characters.',
     *     ],
     *     'email' => [
     *         'Email address is invalid.',
     *     ]
     * ]
     * ```
     *
     * @see getFirstWarnings()
     * @see getFirstWarning()
     */
    public function getWarnings($attribute = null)
    {
        if ($attribute === null) {
            return $this->_warnings === null ? [] : $this->_warnings;
        }

        return isset($this->_warnings[$attribute]) ? $this->_warnings[$attribute] : [];
    }

    /**
     * Returns the first warning of every attribute in the model.
     * @return array the first warnings. The array keys are the attribute names, and the array
     * values are the corresponding warning messages. An empty array will be returned if there is no warning.
     * @see getWarnings()
     * @see getFirstWarning()
     */
    public function getFirstWarnings()
    {
        if (empty($this->_warnings)) {
            return [];
        }

        $warnings = [];
        foreach ($this->_warnings as $name => $es) {
            if (!empty($es)) {
                $warnings[$name] = reset($es);
            }
        }

        return $warnings;
    }

    /**
     * Returns the first warning of the specified attribute.
     * @param string $attribute attribute name.
     * @return string|null the warning message. Null is returned if no warning.
     * @see getWarnings()
     * @see getFirstWarnings()
     */
    public function getFirstWarning($attribute)
    {
        return isset($this->_warnings[$attribute]) ? reset($this->_warnings[$attribute]) : null;
    }

    /**
     * Returns the warnings for all attributes as a one-dimensional array.
     * @param bool $showAllWarnings boolean, if set to true every warning message for each attribute will be shown otherwise
     * only the first warning message for each attribute will be shown.
     * @return array warnings for all attributes as a one-dimensional array. Empty array is returned if no warning.
     * @see getWarnings()
     * @see getFirstWarnings()
     * @since 2.0.14
     */
    public function getWarningSummary($showAllWarnings)
    {
        $lines = [];
        $warnings = $showAllWarnings ? $this->getWarnings() : $this->getFirstWarnings();
        foreach ($warnings as $es) {
            $lines = array_merge($lines, (array)$es);
        }
        return $lines;
    }

    /**
     * Adds a new warning to the specified attribute.
     * @param string $attribute attribute name
     * @param string $warning new warning message
     */
    public function addWarning($attribute, $warning = '')
    {
        $this->_warnings[$attribute][] = $warning;
    }

    /**
     * Adds a list of warnings.
     * @param array $items a list of warnings. The array keys must be attribute names.
     * The array values should be warning messages. If an attribute has multiple warnings,
     * these warnings must be given in terms of an array.
     * You may use the result of [[getWarnings()]] as the value for this parameter.
     * @since 2.0.2
     */
    public function addWarnings(array $items)
    {
        foreach ($items as $attribute => $warnings) {
            if (is_array($warnings)) {
                foreach ($warnings as $warning) {
                    $this->addWarning($attribute, $warning);
                }
            } else {
                $this->addWarning($attribute, $warnings);
            }
        }
    }

    /**
     * Removes warnings for all attributes or a single attribute.
     * @param string|null $attribute attribute name. Use null to remove warnings for all attributes.
     */
    public function clearWarnings($attribute = null)
    {
        if ($attribute === null) {
            $this->_warnings = [];
        } else {
            unset($this->_warnings[$attribute]);
        }
    }

	public function getOneWarning():string
	{
		$warnings = $this->getFirstWarnings(false);
		if( count($warnings) ) {
			return reset($warnings);
		} else {
			return '';
		}
	}

} // trait ModelInfoTrait

