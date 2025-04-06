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
		if (strncmp($what,'db.',3)==0 || $what == 'db') {
			$db = Yii::$app->db;
			$driver_name = $db->driverName;
			$user= $db->username;
			if (!$user) {
				$dsn = $db->dsn;
				if (preg_match("/user=(.*?)(;|$)/", $dsn, $matches)) {
					$user = $matches[1];
				}
			}
			$pwd = $db->password;
			if (!$pwd) {
				$dsn = $db->dsn;
				if (preg_match("/password=(.*?)(;|$)/", $dsn, $matches)) {
					$pwd = $matches[1];
				}
			}
			$dbname = '';
			if ($driver_name == 'sqlite') {
				$dbname = Yii::getAlias(substr($db->dsn,7));
			} else if (preg_match("/dbname=(.*?)(;|$)/", $db->dsn, $matches)) {
				$dbname = $matches[1];
			}
			$dsn = $db->dsn;
			$host = '';
			if (preg_match("/host=(.*)(;|$)/", $dsn, $matches)) {
				$host = $matches[1];
			}
			switch($what) {
				case 'db':
					echo "dsn={$db->dsn}\n";
					echo "driver={$db->driverName}";
					echo "database=$dbname\n";
					echo "user=$user\n";
					echo "pwd=$pwd\n";
					echo "host=$host\n";
					echo "charset={$db->charset}\n";
					echo "collation={$db->charset}_spanish_ci\n";
					break;
				case 'db.dsn':
					echo $db->dsn;
					break;
				case 'db.username':
					echo $user;
					break;
				case 'db.password':
					echo $pwd;
					break;
				case 'db.database':
					echo $dbname;
					break;
				case 'db.host':
					echo $host;
					break;
				case 'db.charset':
					echo $db->charset;
					break;
				case 'db.collation':
					echo $db->charset . '_spanish_ci';
					break;
			}
		}
	}


} // class

