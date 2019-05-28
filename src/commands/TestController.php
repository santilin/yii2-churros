<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace santilin\Churros\commands;
use Yii;
use yii\di\Instance;
use yii\db\Connection;
use yii\console\Controller;
use yii\console\controllers\MigrateController;
/**
 * Churros tests users commands
 *
 *
 * @author Santilín <santi@noviolento.es>
 * @since 1.0
 */
class TestController extends Controller
{
	/** The version of this command */
	const VERSION = '0.1';

	/**
     * Refreshes (migrate/fresh) the test database
     */
    public function actionRefresh()
    {
		$appdir = Yii::getAlias("@app");
		$db = require("$appdir/config/test_db.php");
		$migrateController = new MigrateController('migrate', \Yii::$app, [ 'db' => $db]);
		$migrateController->migrationPath = '@app/database/migrations';
		$mig_ret = $migrateController->runAction('fresh', ['interactive' => 0]);
		if ($mig_ret != 0 ) {
			throw new \Exception("Fallo en la migración de " . \Yii::$app->db->getDatabase());
		}
	}


} // class


