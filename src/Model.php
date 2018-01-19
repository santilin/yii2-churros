<?php namespace santilin\Churros;

use Yii;
use Faker\Factory as Faker;

class Model extends \yii\db\ActiveRecord
{
	use RelationTrait;
	
	public function humanDesc($format=null) {
		return get_called_class();
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
	
} // class Model
