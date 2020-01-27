<?php namespace santilin\churros;

use Yii;
use santilin\churros\helpers\AppHelper;
use santilin\churros\ModelSearchTrait;
use \yii\helpers\ArrayHelper;
use Faker\Factory as Faker;

trait ModelInfoTrait
{
	use RelationTrait;

	static public function bareTableName()
	{
		return strtr( self::tableName(), [ '{' => '', '}' => '', '%' => '' ] );
	}

	public function maleWord($word) {
		if( static::getModelInfo('female') === true ) {
			return $word;
		}
		static $male_words = [
			"a" => "o",
			"as" => "os",
			"la" => "el",
			"La" => "El",
			"las" => "los",
			"Las" => "Los",
			"una" => "un",
			"Una" => "Un",
			"esta" => "este",
			"Esta" => "Este",
			"estas" => "estos",
			"Estas" => "Estos",
			"otra" => "otro",
			"Otra" => "Otra",
			"otras" => "otras",
			"Otras" => "Otras",
		];
		foreach( $male_words as $female_word => $male_word) {
			if( $female_word == $word ) {
				return $male_word;
			}
		}
		return $word;
	}

	public function t( $category, $message, $params = [], $language = null )
	{
		$placeholders = [
			'{la}' => $this->maleWord('la'),
			'{La}' => $this->maleWord('La'),
			'{las}' => $this->maleWord('las'),
			'{Las}' => $this->maleWord('Las'),
			'{una}' => $this->maleWord('una'),
			'{Una}' => $this->maleWord('Una'),
			'{esta}' => $this->maleWord('esta'),
			'{Esta}' => $this->maleWord('Esta'),
			'{estas}' => $this->maleWord('estas'),
			'{Estas}' => $this->maleWord('Estas'),
			'{otra}' => $this->maleWord('otra'),
			'{Otra}' => $this->maleWord('Otra'),
			'{-a}' => $this->maleWord('a'),
			'{-as}' => $this->maleWord('as'),
		];
		$matches = [];
		if( preg_match_all('/({[a-zA-Z0-9\._]+})+/', $message, $matches) ) {
			foreach( $matches[1] as $match ) {
				switch( $match ) {
				case '{title}':
					$placeholders[$match] = lcfirst(static::getModelInfo('title'));
					break;
				case '{title_plural}':
					$placeholders[$match] = lcfirst(static::getModelInfo('title_plural'));
					break;
				case '{Title}':
					$placeholders[$match] = ucfirst(static::getModelInfo('title'));
					break;
				case '{Title_plural}':
					$placeholders[$match] = ucfirst(static::getModelInfo('title_plural'));
					break;
				case '{record}':
					$placeholders[$match] = $this->recordDesc();
					break;
				case '{record_long}':
					$placeholders[$match] = $this->recordDesc('long');
					break;
				case '{record_short}':
					$placeholders[$match] = $this->recordDesc('short');
					break;
				}
			}
		}
		$translated = Yii:: t($category, $message, $params, $language);
		return strtr($translated, $placeholders);
	}

	public function recordDesc($format=null)
	{
		if( $format == null || $format == 'long' || $format == 'link' ) {
			$format = self::getModelInfo('record_desc_format_long');
		} elseif( $format == 'short' ) {
			$format = self::getModelInfo('record_desc_format_short');
		}
		$values = $matches = [];
		if( preg_match_all('/{([a-zA-Z0-9\._]+)(\%([^}])*)*}+/', $format, $matches) ) {
			foreach( $matches[0] as $n => $match ) {
				$value = ArrayHelper::getValue($this, $matches[1][$n]);
				$sprintf_part = $matches[2][$n];
				if( $sprintf_part == '' ) {
					$sprintf_part = "%s";
				} else {
					if( preg_match('/\.([0-9]+)/', $sprintf_part, $m ) ) {
						$len = intval($m[1]);
						if( strlen(strval($value)) > $len-3 ) {
							$value = substr($value, 0, $len-3) .
							'...';
						}
					}
				}
				$format = str_replace($match, $sprintf_part, $format);
				$values[] = $value;
			}
			return sprintf($format, ...$values);
		} else {
			return $format;
		}
	}

	public function linkTo($action, $prefix = '', $format = 'short')
	{
		$url = $prefix;
		if ($url != '') {
			$url .= "/";
		}
		$url .= $this->controllerName();
		if( $this->getIsNewRecord() ) {
			$url .= '/create';
			return \yii\helpers\Html::a($this->t("New {title}"),
					$url);
		} else {
			$url .= "/$action";
			return \yii\helpers\Html::a($this->recordDesc($format),
					[$url, 'id' => $this->getPrimaryKey() ]);
		}
	}

	public function getFileAttributes()
	{
		$ret = [];
		foreach( $this->rules() as $key => $rule ) {
			if( $rule[1] == 'image' || $rule[1] == 'file' ) {
				if( is_array($rule[0]) ) {
					$ret = array_merge( $ret, $rule[0]);
				} else {
					$ret[] = explode(",", $rule[0]);
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

	public function setDefaultValues()
	{
	}

	// @todo put all these in a trait
	public function setFakerValues($faker)
	{
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

    static public function createFromFaker($number = 1, $language = null)
    {
		if( $language == null ) {
			$language = \app\helpers\AppHelper::getAppLocaleLanguage();
		}
		$faker = Faker::create($language);
		$ret = [];
		for( $count = 0; $count < $number; ++$count ) {
			$modelname = get_called_class();
			$model = new $modelname;
			$model->setFakerValues($faker);
			if( $number == 1 ) {
				return $model;
			} else {
				$ret[] = $model;
			}
		}
		return $ret;
    }

    static public function createFromFixture($fixture_file, $fixture_id)
    {
		$models = include($fixture_file);
		if( isset($models[$fixture_id]) ) {
			$ret = [];
			for( $count = 0; $count < $number; ++$count ) {
				$model = new self($models[$fixture_id]);
				if( $number == 1 ) {
					return $model;
				} else {
					$ret[] = $model;
				}
			}
			return $ret;
		} else {
			throw new \app\helpers\ProgrammerException("No se encuentra un " . self::className() . "de id $fixture_id en el fichero $fixture_file");
		}
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
			if( $rel_info['modelClass'] == $related_model->className() ) {
				if( $rel_info['type'] == 'BelongsToOne' ) {
					$related_field = $rel_info['right'];
				} else {
					$related_field = $rel_info['left'];
				}
				list($table, $field) = ModelSearchTrait::splitFieldName($related_field);
				return $field;
			}
		}
		throw new \Exception( self::className() . " no está relacionado con " . $related_model->className() );
    }

    public function getRelatedModelClass($relation_name)
    {
		if( isset(self::$relations[$relation_name]) ) {
			$rel_info = $this->relations[$relation_name];
			return $rel_info['model'];
		} else {
			throw new \Exception( self::className() . " no está relacionado con " . $related_model->className() );
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
							'src' => "/uploads/$filename",
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
	static public function getCodeDescFields() {
		$desc_field = static::getModelInfo('desc_field');
		$code_field = static::getModelInfo('code_field');
		if ($code_field != '' && $desc_field != '' ) {
			return [ $code_field, $desc_field ];
		} else if( $code_field != '' ) {
			return [ $code_field, '' ];
		} else if( $desc_field != '' ) {
			return [ $desc_field, '' ];
		} else {
			return [ $this->getPrimaryKey(), '' ];
		}
	}

	public function getFormattedValue($attr)
	{
		$method_name = "get" . ucwords(str_replace("_","",$attr)) . "Label";
		if( method_exists($this, $method_name) ) {
			return $this->$method_name();
		}
		return $this->$attr;
	}

	static public function addWithSep(& $source, $add, $sep = ', ')
	{
		if( !empty($add) ) {
			$source = "$source$sep$add";
		}
	}

	public function IAmOwner()
	{
		$blameable = $this->getBehavior('blameable');
		if( $blameable ) {
			$created_by = $blameable->createdByAttribute;
			$author = $this->$created_by;
			return $author != Yii::$app->user->id;
		} else {
			return false;
		}
	}

} // trait ModelInfoTrait

