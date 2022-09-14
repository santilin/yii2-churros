<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace santilin\churros\console\controllers;
use Yii;
use yii\di\Instance;
use yii\base\InvalidConfigException;
use yii\db\Connection;
use yii\console\Controller;

/**
 * Churros dump and seed commands
 *
 * @author Santilín <santi@noviolento.es>
 * @since 1.0
 */
class DevelController extends Controller
{
	/** The version of this command */
	const VERSION = '0.1';
	const CAPELDIR = '/home/santilin/devel/capel';

	public function actionCheckCapel($capelfname = null, $schemafname = null)
	{
		// https://json-schema.org/understanding-json-schema/index.html
		$validator = new \JsonSchema\Validator();
		if( $capelfname == null ) {
			$capelfname = basename(getcwd()) . '.capel.json';
		}
		if( $schemafname == null ) {
			$schemafname = self::CAPELDIR . '/share/definitions/program_schema.json';
		}
		echo "Validating $capelfname aginst $schemafname\n";
		$data = json_decode(file_get_contents($capelfname));
		$validator->validate($data, (object) ['$ref' => 'file://' . realpath($schemafname)]);
		if ($validator->isValid()) {
			echo "The supplied JSON validates against the schema.\n";
		} else {
			echo "JSON does not validate. Violations:\n";
			foreach ($validator->getErrors() as $error) {
				echo sprintf("[%s] %s\n", $error['property'], $error['message']);
			}
		}
	}


} // class

