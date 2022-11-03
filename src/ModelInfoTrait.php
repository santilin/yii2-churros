<?php namespace santilin\churros;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use santilin\churros\helpers\{YADTC,AppHelper};
use santilin\churros\ModelSearchTrait;

trait ModelInfoTrait
{
	use RelationTrait;

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
		if( ($language == null || $language == 'es') ) {
			$male_words = AppHelper::SPANISH_MALE_WORDS;
		} else {
			$male_words = [];
		}
		$matches = $placeholders = [];
		$female = $this->getModelInfo('female');
		if( preg_match_all('/({([a-zA-Z0-9\._]+)})+/', $message, $matches) ) {
			foreach( $matches[2] as $match ) {
				if( substr($match,0,6) == 'model.' ) {
					$fld = substr($match, 6);
					$placeholders[$match] = ArrayHelper::getValue($this,$fld,'');
				} else switch( $match ) {
				case 'title':
					$placeholders[$match] = lcfirst(static::getModelInfo('title'));
					break;
				case 'title_plural':
					$placeholders[$match] = lcfirst(static::getModelInfo('title_plural'));
					break;
				case 'Title':
					$placeholders[$match] = ucfirst(static::getModelInfo('title'));
					break;
				case 'Title_plural':
					$placeholders[$match] = ucfirst(static::getModelInfo('title_plural'));
					break;
				case 'record':
					$placeholders[$match] = $this->recordDesc();
					break;
				case 'record_link':
					$placeholders[$match] = $this->recordDesc('link');
					break;
				case 'record_long':
					$placeholders[$match] = $this->recordDesc('long');
					break;
				case 'record_medium':
					$placeholders[$match] = $this->recordDesc('medium');
					break;
				case 'record_short':
					$placeholders[$match] = $this->recordDesc('short');
					break;
				default:
					if( isset($male_words[$match]) ) {
						if( $female )  {
							$placeholders[$match] = $match;
						} else {
							$placeholders[$match] = $male_words[$match];
						}
					}
				}
			}
		}
		$placeholders = array_merge($placeholders, $params);
		return Yii:: t($category, $message, $placeholders, $language);
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

	public function getFileAttributes()
	{
		$ret = [];
		foreach( $this->rules() as $key => $rule ) {
			if( $rule[1] == 'image' || $rule[1] == 'file' ) {
				$multiple = isset($rule['maxFiles']) && $rule['maxFiles'] != 1;
				$attrs = $rule[0];
				if( !is_array($attrs) ) {
					$attrs = explode(',', $attrs);
				}
				foreach( $attrs as $attr) {
					$ret[$attr] = $multiple;
				}
			}
		}
		return $ret;
	}

	public function increment( $fldname, $increment, $conds = [], $usegaps = true)
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
        $fval =  floatval($val) + floatval($increment);
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

    static public function valuesAndLabels()
    {
		$model = new static;
		$code_field = $model->getModelInfo('code_field');
		$desc_field = $model->getModelInfo('desc_field');
		$id_field = "ID"; /// @todo $model->getPrimaryKey();
		if( $code_field && $desc_field ) {
			return ArrayHelper::map($model->find()->select([$id_field, "CONCAT({{" . $code_field . "}}, '.- ', {{" . $desc_field . "}}) AS DESCRIPTION"])->asArray()->all(), $id_field, 'DESCRIPTION');
		} else if( $code_field ) {
			return ArrayHelper::map($model->find()->select([$id_field, $code_field])->asArray()->all(), $id_field, $code_field);
		} else if( $desc_field ) {
			return ArrayHelper::map($model->find()->select([$id_field, $desc_field])->asArray()->all(), $id_field, $code_field);
		} else {
			return [];
		}
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

	public function getImageData($fldname, $index=0)
	{
		$fldvalue = $this->$fldname;
		if( $fldvalue != '') {
			try {
				$uns_images = unserialize($fldvalue);
				foreach( $uns_images as $filename => $titleandsize) {
					if( $index-- == 0 ) {
						return (object) [
							'src' => Yii::getAlias("@uploads/$filename"),
							'title' => $titleandsize[0],
							'size' => $titleandsize[1]
						];
					}
				}
			} catch (\Exception $e ) {
			}
		}
		return (object)[ 'src' => '', 'title' => '', 'size' => 0 ];
	}

	public function addErrorsFrom(ActiveRecord $model, $key = null)
	{
		if( $key === null ) {
			$key = strtr(static::tableName(), '{%}', '   ') . '_';
		}
		foreach( $model->getErrors() as $k => $error ) {
			foreach( $error as $err_msg ) {
				$this->addError(  $key . $k, $err_msg);
			}
		}
	}


	public function getOneError()
	{
		$errors = $this->getErrorSummary(false);
		if( count($errors) ) {
			return $errors[0];
		} else {
			return null;
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
			$r0 = explode(',',static::getModelInfo('code_field'));
			$r1 = explode(',',static::getModelInfo('desc_field'));
			return array_merge($r0,$r1);
		} else if (isset(static::$relations[$relname])) {
			$relmodelname = static::$relations[$relname]['modelClass'];
			$relmodel = $relmodelname::instance();
			return $relmodel::findCodeAndDescFields();
		} else {
			return [];
		}
	}

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

	public function unlinkImages($images)
	{
		if( !is_array($images) ) {
			$tmp = @unserialize($images);
			if( $tmp != null ) {
				$images = $tmp;
			}
		}
		if( empty($images) ) {
			return true;
		}
		if( !is_array($images) ) {
			$images = [$images];
		}
		foreach( $images as $image ) {
			$oldfilename = Yii::getAlias("@uploads/$image");
			if (file_exists($oldfilename) && !@unlink($oldfilename)) {
				$model->addError($attr, "No se ha podido borrar el archivo $oldfilename" . posix_strerror($file->error));
				return false;
			}
		}
		return true;
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


} // trait ModelInfoTrait

