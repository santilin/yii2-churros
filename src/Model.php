<?php 

namespace santilin\Churros;

class Model extends \yii\db\ActiveRecord
{
	public function humanDesc($format) {
		return __toString();
	}
}
