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
use yii\rbac\{BaseManager,Item,Role};
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

	/**
	 * Creates the permissions for a model inside a module
	 */
	public function createControllerPermissions(string $module_id, string $model_name, array $controller,
												Role $visora, Role $editora, $auth = null)
	{
		if (!$auth) {
			$auth = $this->authManager;
		}
		$model_class = $controller['class'];
		$model = $model_class::instance();
		$model_title = $model->t('app', "{title_plural}");
		$model_perm_name = $module_id . '.' . $model_name;

		$model_editora = AuthHelper::createOrUpdateRole(
			$model_perm_name . '.editor',
			Yii::t('churros', '{model} editor', ['model' => $model_title]), $auth);
		AuthHelper::echoLastMessage();
		$model_visora = AuthHelper::createOrUpdateRole(
			$model_perm_name . '.viewer',
			Yii::t('churros', '{model} viewer', ['model' => $model_title]), $auth);
		AuthHelper::echoLastMessage();

		if( !$auth->hasChild($visora, $model_visora) ) {
			$auth->addChild($visora, $model_visora);
			echo "role '{$model_visora->name}' added to role '{$visora->name}'\n";
// 				} else {
// 					echo "Warning: permission {$permission->name} already exists in role {$visora->name}\n";
		}
		if( !$auth->hasChild($editora, $model_editora) ) {
				$auth->addChild($editora, $model_editora);
				echo "role '{$model_editora->name}' added to role '{$editora->name}'\n";
// 			} else {
// 				echo "Warning: permission {$permission->name} already exists in role {$editora->name}\n";
			}

		foreach ($controller['perms'] as $perm_name) {
			$perm_desc = Yii::t('churros', ucfirst($perm_name));
			$permission = AuthHelper::createOrUpdatePermission(
				$model_perm_name . ".$perm_name",
				$perm_desc . ' ' . $model_title, $auth);
			AuthHelper::echoLastMessage();

			$add_to_visora = ($perm_name == 'view' || $perm_name == 'index' || $perm_name == 'menu' );
			if( $add_to_visora ) {
				if( !$auth->hasChild($model_visora, $permission) ) {
					$auth->addChild($model_visora, $permission);
					echo "permission '{$permission->name}' added to role '{$model_visora->name}'\n";
// 				} else {
// 					echo "Warning: permission {$permission->name} already exists in role {$model_visora->name}\n";
				}
			}
			if( !$auth->hasChild($model_editora, $permission) ) {
				$auth->addChild($model_editora, $permission);
				echo "permission '{$permission->name}' added to role '{$model_editora->name}'\n";
// 			} else {
// 				echo "Warning: permission {$permission->name} already exists in role {$model_editora->name}\n";
			}
// 		$model_editora_own = AuthHelper::createOrUpdateRole(
// 			$model_perm_name . '.editor.own',
// 			Yii::t('churros', 'Their own {model} editor', ['model' => $model_title]), $auth);
// 		AuthHelper::echoLastMessage();
// 		$model_visora_own = AuthHelper::createOrUpdateRole(
// 			$model_perm_name . '.viewer.own',
// 			Yii::t('churros', 'Their own {model} viewer', ['model' => $model_title]), $auth);
// 		AuthHelper::echoLastMessage();

// 			$own_perm_name = "$model_perm_name.$perm_name" . ($perm_name == 'create'?'':'.own');
// 			$permission_own = AuthHelper::createOrUpdatePermission( $own_perm_name,
// 				$perm_desc .' ' . $model->t('churros', 'their own {title_plural}'), $auth);
// 			AuthHelper::echoLastMessage();
// 			if( !$auth->hasChild($model_editora_own, $permission_own) ) {
// 				$auth->addChild($model_editora_own, $permission_own);
// 				echo "permission '{$permission_own->name}' added to role '{$model_editora_own->name}'\n";
// 			}
// 			if( $add_to_visora ) {
// 				if( !$auth->hasChild($model_visora_own, $permission_own) ) {
// 					$auth->addChild($model_visora_own, $permission_own);
// 					echo "permission '{$permission_own->name}' added to role '{$model_editora_own->name}'\n";
// 				}
// 			}
		}
	}

	/**
	 * Creates the permissions for a module
	 */
	public function createModulePermissions(string $module_id, array $module_info, $auth = null)
	{
		if (!$auth) {
			$auth = $this->authManager;
		}
		$visora = AuthHelper::createOrUpdateRole("$module_id.viewer",
			Yii::t('churros', "View all $module_id records"), $auth);
		AuthHelper::echoLastMessage();
		$editora = AuthHelper::createOrUpdateRole("$module_id.editor",
			Yii::t('churros', "Edit all $module_id records"), $auth);
		AuthHelper::echoLastMessage();
		foreach ($module_info['controllers']??[] as $cname => $controller) {
			$this->createControllerPermissions($module_id, $cname, $controller, $visora, $editora);
		}
// 		$auth = $this->authManager;
// 		AuthHelper::createOrUpdatePermission("$module_id.menu",
// 			Yii::t('churros', 'Access to \'{module}\' module menu',
// 			[ 'module' => $module_title?:$module_id ]), $auth);
// 		AuthHelper::echoLastMessage();
// 		$role_all_name = "$module_id.all.menu";
// 		$role_all = AuthHelper::createOrUpdateRole($role_all_name,
// 			Yii::t('churros', 'Access to all menus of module {module}', [ 'module' => $module_id ]), $auth);
// 		AuthHelper::echoLastMessage();
// 		AuthHelper::createOrUpdatePermission("$module_id.site.index",
// 			Yii::t('churros', 'Access to \'{module}\' site index',
// 			[ 'module' => $module_title?:$module_id ]), $auth);
// 		AuthHelper::echoLastMessage();
// 		AuthHelper::createOrUpdatePermission("$module_id.site.about",
// 			Yii::t('churros', 'Access to \'{module}\' site about',
// 			[ 'module' => $module_title?:$module_id ]), $auth);
// 		AuthHelper::echoLastMessage();
// 		AuthHelper::createOrUpdatePermission("$module_id.reports",
// 			Yii::t('churros', 'Access to \'{module}\' reports',
// 			[ 'module' => $module_title?:$module_id ]), $auth);
// 		AuthHelper::echoLastMessage();
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
			if( preg_match( '/([A-Za-z_][A-Za-z_0-9]*).(index.own|index|create|view.own|edit.own|delete.own|report.own|view|edit|delete|report)/', $name, $m ) ) {
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

	/**
	 * Lists the roles and permissions of a role
	 * @param string $role name
	 */
	public function actionListRole($role)
	{
		$users_ids = $this->authManager->getUserIdsByRole($role);
		$subroles = $this->authManager->getChildRoles($role);
		if( count($subroles) ) {
			$s_subroles = '';
			foreach($subroles as $subrol) {
				if( $subrol->name != $role ) {
					$s_subroles .= $subrol->name . ", ";
				}
			}
			if( $s_subroles ) {
				$this->stdout("- ".$role.":roles:$s_subroles\n");
			}
		}
		$role_perms = $this->authManager->getPermissionsByRole($role);
		if( count($role_perms) ) {
			$this->stdout("- ".$role.":perms:");
			foreach($role_perms as $perm) {
				$this->stdout($perm->name . ", ");
			}
			$this->stdout("\n");
		} else if( empty($s_subroles) ) {
			$this->stdout("- ". $role. "\n");
		}
	}


	public function actionAssignPermToUser($perm_name, $user_id)
	{
		$permission = $this->authManager->getItem($perm_name);
		if( $permission == null ) {
			return false;
		}
		$this->authManager->assign($permission, $user_id);
	}

	public function actionAssignPermToRole($perm_name, $role_name)
	{
		$permission = $this->authManager->getItem($perm_name);
		if( $permission == null ) {
			return false;
		}
		$role = $this->authManager->getRole($role_name);
		if( !$role ) {
			throw new \Exception( "$role_name: role not found" );
		}
		$this->authManager->addChild($role, $permission);
	}


	public function actionRemovePermFromRole($perm_name, $role_name)
	{
		AuthHelper::removePermFromRole($perm_name, $role_name, $this->authManager);
	}

	public function actionRemoveRole($role_name)
	{
		AuthHelper::removeRoles($role_name, $this->authManager);
	}

	public function actionRemoveAll()
	{
		$this->authManager->removeAll();
	}

} // class

