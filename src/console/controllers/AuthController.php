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
use yii\rbac\{BaseManager,Item};
use yii\console\Controller;

/**
 * Churros auth controller
 *
 * @author Santilín <software@noviolento.es>
 * @since 1.0
 */
class AuthController extends Controller
{
	/** The version of this command */
	const VERSION = '0.1';

	public $db = 'db';
	public $authManager = 'authManager';

    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['create', 'delete']
        );
    }

    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'f' => 'format',
            't' => 'truncateTables',
            'c' => 'createFile',
            'p' => 'seedersPath'
        ]);
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * It checks the existence of the [[migrationPath]].
     * @param \yii\base\Action $action the action to be executed.
     * @return bool whether the action should continue to be executed.
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            $this->db = Instance::ensure($this->db, Connection::className());
            $this->authManager = Instance::ensure($this->authManager, BaseManager::className());

            if ($this->authManager instanceof DbManager) {
                $this->authManager->db = $this->db;
            }
        }
        return true;
    }


	/**
	 * Creates the permissions for a model
	 */
	public function actionCreateForModel($model_name)
	{
		AuthHelper::createModelPermissions($model_name, $this->auth);
	}

	/**
	 * Seeds the current schema with the specified file
	 */
	public function actionListAll($type = null)
	{
		$perms = $this->authManager->getItems(Item::TYPE_PERMISSION);
		$no_model_perms = [];
		$prev_model = null;
		$this->stdout("== PERMISSIONS == \n");
		foreach( $perms as $perm ) {
			$name = $perm->name;
			if( preg_match( '/([A-Za-z_][A-Za-z_0-9]*)_(Crea|View_Own|Edit_Own|Dele_Own|Repo_Own|View|Edit|Dele|Repo)/', $name, $m ) ) {
				if( $m[1] == "Reports" ) {
					continue;
				}
				if( $prev_model == $m[1] ) {
					$this->stdout(', ' . $m[2]);
				} else {
					if( $prev_model == null ) {
						$this->stdout("= MODELS =\n");
					} else {
						$this->stdout("\n");
					}
					$prev_model = $m[1];
					$this->stdout(str_pad($m[1],15,' ') . $m[2]);
				}
			} else {
				$no_model_perms[] = $perm;
			}
		}
		if( $prev_model ) {
			$this->stdout("\n");
		}
		$this->stdout("= OTHER = \n");
		foreach( $no_model_perms as $perm ) {
			$this->stdout($perm->name . "\n");
		}
		$roles = $this->authManager->getItems(Item::TYPE_ROLE);
		$this->stdout("\n== ROLES == \n");
		foreach( $roles as $role ) {
			$this->stdout($role->name . "\n");
		}

	}

} // class

