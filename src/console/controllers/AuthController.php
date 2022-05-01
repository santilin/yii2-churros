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
use santilin\churros\helpers\AuthHelper;

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
    
	public function actionRemoveAll()
	{
		$this->authManager->removeAll();
	}

	/**
	 * Creates the permissions for a model
	 */
	public function createModelPermissions($model_name, $model_class)
	{
		AuthHelper::createModelPermissions($model_name, $model_class, $this->authManager);
	}

	/**
	 * Creates the menu access permissiones for a model
	 */
	public function createModuleModelPermissions($module_name, $model_name, $model_class)
	{
		AuthHelper::createModuleModelPermissions($module_name, $model_name, $model_class, $this->authManager);
	}

	/**
	 * Creates the permissions for a module
	 */
	public function createModulePermissions($module_name)
	{
		AuthHelper::createPermission("module.$module_name.menu", "Acceso al menu del módulo $module_name", $this->authManager);
	}
	


	/**
	 * Lists all permissions, optionally by type
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
		$users_ids = [];
		$roles = $this->authManager->getItems(Item::TYPE_ROLE);
		$this->stdout("\n== ROLES == \n");
		foreach( $roles as $role ) {
			$users_ids = array_merge($users_ids, $this->authManager->getUserIdsByRole($role->name));
			$subroles = $this->authManager->getChildRoles($role->name);
			if( count($subroles) ) {
				$s_subroles = '';
				foreach($subroles as $subrol) {
					if( $subrol->name != $role->name ) {
						$s_subroles .= $subrol->name . ", ";
					}
				}
				if( $s_subroles ) {
					$this->stdout("- ".$role->name.":roles:$s_subroles\n");
				}
			}
			$role_perms = $this->authManager->getPermissionsByRole($role->name);
			if( count($role_perms) ) {
				$this->stdout("- ".$role->name.":perms:");
				foreach($role_perms as $perm) {
					$this->stdout($perm->name . ", ");
				}
				$this->stdout("\n");
			} else if( empty($s_subroles) ) {
				$this->stdout("- ". $role->name. "\n");
			}
		}

		$this->stdout("\n== USERS' ASSIGNMENTS == \n");
		$user_class = Yii::$app->user->identityClass;
		$user = new $user_class;
		$users = $user->find()->all();
		foreach( $users as $user ) {
			$this->stdout("user:{$user->id}:{$user->username}:");
			$assignments = $this->authManager->getAssignments($user->id);
			foreach( $assignments as $as ) {
				$this->stdout($as->roleName . ", ");
			}
			$this->stdout("\n");
		}
	}

	public function actionAssignPermToUser($perm_name, $user_id, $auth = null)
	{
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		$permission = $auth->getItem($perm_name);
		if( $permission == null ) {
			return false;
		}
		$auth->assign($permission, $user_id);
	}	
	
	
} // class

