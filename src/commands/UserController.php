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
use app\Models\User;
/**
 * Churros console users commands
 *
 *
 * @author Santilín <santi@noviolento.es>
 * @since 1.0
 */
class UserController extends Controller
{
	/** The version of this command */
	const VERSION = '0.1';


	/**
     * Changes a user password
     *
     * @param string $schemaName the schema name (optional)
     */
    public function actionChangePassword($username, $new_password)
    {
		$user = User::find([ "username", "=", $username])->one();
		if (!$user) {
			throw new Exception("User $username not found");
		}
		$user->password_hash = Yii::$app->security->generatePasswordHash($new_password);
		$user->save();
	}


} // class

