<?php 

namespace santilin\Churros;

class Model extends \yii\db\ActiveRecord
{
	public function humanDesc($format=null) {
		return get_called_class();
	}
}
