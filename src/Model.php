<?php namespace santilin\Churros;

use Yii;


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
	
} // class Model
