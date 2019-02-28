<?php namespace santilin\Churros;

use Yii;
use santilin\Churros\helpers\AppHelper;
use \yii\helpers\ArrayHelper;
use Faker\Factory as Faker;

trait Model
{
	use RelationTrait;

	public function maleWord($word) {
		if( $this->getModelInfo('female') === true ) {
			return $word;
		}
		static $male_words = [
			"la" => "el",
			"La" => "El",
			"las" => "los",
			"Las" => "Los",
			"una" => "uno",
			"Una" => "Uno"
		];
		foreach( $male_words as $female_word => $male_word) {
			if( $female_word == $word ) {
				return $male_word;
			}
		}
		return $word;
	}

	public function t( $category, $message, $params = [], $language = null ) {
		$placeholders = [
			'{title}' => lcfirst($this->getModelInfo('title')),
			'{title_plural}' => lcfirst($this->getModelInfo('title_plural')),
			'{Title}' => ucfirst($this->getModelInfo('title')),
			'{Title_plural}' => ucfirst($this->getModelInfo('title_plural')),
			'{record}' => $this->recordDesc(),
			'{record_long}' => $this->recordDesc('long'),
			'{la}' => $this->maleWord('la'),
			'{La}' => $this->maleWord('La'),
			'{las}' => $this->maleWord('las'),
			'{Las}' => $this->maleWord('Las'),
			'{una}' => $this->maleWord('una'),
			'{Una}' => $this->maleWord('Una'),
		];
		$message = strtr($message, $placeholders);
		return Yii::t($category, $message, $params, $language);
	}

	public function recordDesc($format=null) {
		return "(not defined)";
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

	public function increment( $fldname, $increment, $conds = '', $usegaps = true)
	{
		$tablename = $this->tableName();
		if( $usegaps ) {
			$sql = "SELECT [[$fldname]]";
		} else {
			$sql = "SELECT MAX([[$fldname]])";
		}
		$sql .= " FROM $tablename";
		if( $usegaps || $conds != '' ) {
			$sql .= " WHERE ";
		}
		if( $conds != '' ) {
			$sql .= "($conds)";
		}
		if( $usegaps ) {
			if( $conds != '' ) {
				$sql .= " AND ";
			}
			$sql .= "([[$fldname]]+$increment NOT IN (SELECT [[$fldname]] FROM $tablename";
			if( $conds != '' ) {
				$sql .= " WHERE ($conds)";
			}
			$sql .= ") )";
		}
//     try {
        return Yii::$app->db->createCommand( $sql )->queryScalar() + intval($increment);
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
		return $prefix . lcfirst(AppHelper::stripNamespaceFromClassName($this));
    }

    public function getRelatedFieldForModel($related_model)
    {
		return null;
    }

} // class Model
