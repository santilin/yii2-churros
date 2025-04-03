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


	public function actionPrintConfig(string $what)
	{
		switch($what) {
			case 'db.dsn':
				echo Yii::$app->db->dsn;
				break;
			case 'db.username':
				$user= Yii::$app->db->username;
				if (!$user) {
					$dsn = Yii::$app->db->dsn;
					if (preg_match("/user=(.*?)(;|$)/", $dsn, $matches)) {
						$user = $matches[1];
					}
				}
				echo $user;
				break;
			case 'db.password':
				$pwd = Yii::$app->db->password;
				if (!$pwd) {
					$dsn = Yii::$app->db->dsn;
					if (preg_match("/password=(.*?)(;|$)/", $dsn, $matches)) {
						$pwd = $matches[1];
					}
				}
				echo $pwd;
				break;
			case 'db.database':
				$name = '';
				$dsn = Yii::$app->db->dsn;
				if (preg_match("/dbname=(.*?)(;|$)/", $dsn, $matches)) {
					$name = $matches[1];
				}
				echo $name;
				break;
			case 'db.host':
				$dsn = Yii::$app->db->dsn;
				$host = '';
				if (preg_match("/host=(.*)(;|$)/", $dsn, $matches)) {
					$host = $matches[1];
				}
				echo $host;
				break;
			case 'db.charset':
				echo Yii::$app->db->charset;
				break;
			case 'db.collation':
				echo Yii::$app->db->charset . '_spanish_ci';
				break;
		}
	}


} // class

