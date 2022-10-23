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

	/**
	 * Creates the permissions for a model
	 */
	public function createModelPermissions($model_name, $model_class)
	{
		$auth = $this->authManager;
		$visora = AuthHelper::createOrUpdateRole('app.visor',
			Yii::t('churros', 'View all files'), $auth);
		AuthHelper::echoLastMessage();
		$editora = AuthHelper::createOrUpdateRole('app.editor',
			Yii::t('churros', 'Edit all files'), $auth);
		AuthHelper::echoLastMessage();

		$model = $model_class::instance();
		$model_title = $model->t('app', "{title_plural}");
		$model_editora = AuthHelper::createOrUpdateRole(
			$model_name . '.editor',
			Yii::t('churros', '{model} editor', ['model' => $model_title]), $auth);
		AuthHelper::echoLastMessage();

		$model_visora = AuthHelper::createOrUpdateRole(
			$model_name . '.visor',
			Yii::t('churros', '{model} visor', ['model' => $model_title]), $auth);
		AuthHelper::echoLastMessage();

		$model_editora_own = AuthHelper::createOrUpdateRole(
			$model_name . '.editor.own',
			Yii::t('churros', 'Their own {model} editor', ['model' => $model_title]), $auth);
		AuthHelper::echoLastMessage();

		$model_visora_own = AuthHelper::createOrUpdateRole(
			$model_name . '.visor.own',
			Yii::t('churros', 'Their own {model} viewer', ['model' => $model_title]), $auth);
		AuthHelper::echoLastMessage();


		foreach( [ 'create' => Yii::t('churros', 'Create'),
					'view' => Yii::t('churros', 'View'),
					'edit' => Yii::t('churros', 'Edit'),
					'delete' => Yii::t('churros', 'Delete'),
					'index' => Yii::t('churros', 'List')
				  ] as $perm_name => $perm_desc) {
			$permission = AuthHelper::createOrUpdatePermission(
				$model_name . ".$perm_name",
				$perm_desc . ' ' . $model_title, $auth);
			AuthHelper::echoLastMessage();

			$add_to_visora = ($perm_name == 'view' || $perm_name == 'index');
			if( $add_to_visora ) {
				if( !$auth->hasChild($visora, $permission) ) {
					$auth->addChild($visora, $permission);
					echo "permission {$permission->name} added to role {$visora->name}\n";
// 				} else {
// 					echo "Warning: permission {$permission->name} already exists in role {$visora->name}\n";
				}
				if( !$auth->hasChild($model_visora, $permission) ) {
					$auth->addChild($model_visora, $permission);
					echo "permission {$permission->name} added to role {$model_visora->name}\n";
// 				} else {
// 					echo "Warning: permission {$permission->name} already exists in role {$model_visora->name}\n";
				}
			}
			if( !$auth->hasChild($model_editora, $permission) ) {
				$auth->addChild($model_editora, $permission);
				echo "permission {$permission->name} added to role {$model_editora->name}\n";
// 			} else {
// 				echo "Warning: permission {$permission->name} already exists in role {$model_editora->name}\n";
			}
			if( !$auth->hasChild($editora, $permission) ) {
				$auth->addChild($editora, $permission);
				echo "permission {$permission->name} added to role {$editora->name}\n";
// 			} else {
// 				echo "Warning: permission {$permission->name} already exists in role {$editora->name}\n";
			}
			if( $perm_name != 'create' ) {
				$permission_own = AuthHelper::createOrUpdatePermission(
					$model_name . ".$perm_name.own",
					$perm_desc .' ' . $model->t('churros', 'their own {title_plural}'), $auth);
				AuthHelper::echoLastMessage();
				if( !$auth->hasChild($model_editora_own, $permission_own) ) {
					$auth->addChild($model_editora_own, $permission_own);
					echo "permission {$permission_own->name} added to role {$model_editora_own->name}\n";
				}
				if( $add_to_visora ) {
					if( !$auth->hasChild($model_visora_own, $permission_own) ) {
						echo "permission {$permission_own->name} added to role {$model_editora_own->name}\n";
						$auth->addChild($model_visora_own, $permission_own);
					}
				}
			}
		}
	}

	public function createModuleModelPermissions($module_id, $model_name, $model_class)
	{
		$auth = $this->authManager;

		$model = $model_class::instance();
		$model_title = $model->t('app', "{title_plural}");

		$role_all_name = "module.$module_id.menu.all";
		$role_all = $this->authManager->getRole($role_all_name);

		$perm_name = "module.$module_id.menu.$model_name";
		$permission = AuthHelper::createOrUpdatePermission($perm_name,
			Yii::t('churros', 'Access to {model_title} menu for {module_name} module',
				[ 'model_title' => $model_title, 'module_name' => $module_id ]), $auth);
		AuthHelper::echoLastMessage();
		if( !$auth->hasChild($role_all, $permission) ) {
			$auth->addChild($role_all, $permission);
			echo "{$permission->name}: permission added to role {$role_all->name}\n";
		}
	}

	/**
	 * Creates the permissions for a module
	 */
	public function createModulePermissions($module_id, $module_title = null)
	{
		$auth = $this->authManager;
		AuthHelper::createOrUpdatePermission("module.$module_id.menu",
			Yii::t('churros', 'Access to \'{module}\' module menu',
			[ 'module' => $module_title?:$module_id ]), $auth);
		AuthHelper::echoLastMessage();
		$role_all_name = "module.$module_id.menu.all";
		$role_all = $this->authManager->getRole($role_all_name);
		if( !$role_all ) {
			$role_all = AuthHelper::createOrUpdateRole($role_all_name,
				Yii::t('churros', 'Access to all models of module {module}', [ 'module' => $module_id ]), $auth);
			AuthHelper::echoLastMessage();
		}
		AuthHelper::createOrUpdatePermission("module.$module_id.site.index",
			Yii::t('churros', 'Access to \'{module}\' module site',
			[ 'module' => $module_title?:$module_id ]), $auth);
		AuthHelper::echoLastMessage();
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

	public function actionAssignPermToRole($perm_name, $role_name, $auth = null)
	{
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		$permission = $auth->getItem($perm_name);
		if( $permission == null ) {
			return false;
		}
		$role = $auth->getRole($role_name);
		if( !$role ) {
			throw new \Exception( "$role_name: role not found" );
		}
		$auth->addChild($role, $permission);
	}


	public function actionRemovePermFromRole($perm_name, $role_name, $auth = null)
	{
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		$child = $auth->getItem($perm_name);
		if( $child == null ) {
			return;
		}
		$parent = $auth->getItem($role_name);
		if( $parent == null ) {
			return;
		}
		$auth->removeChild($parent, $child);
	}

	public function actionRemoveAll()
	{
		$this->authManager->removeAll();
	}

} // class

