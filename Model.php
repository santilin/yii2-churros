<?php 

namespace churros;

class Model extends \yii\db\ActiveRecord
{
	public function humanDesc($format) {
		return __toString();
	}
}
