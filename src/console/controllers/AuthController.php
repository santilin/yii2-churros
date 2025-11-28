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
use yii\helpers\{Console,StringHelper};
use yii\db\Connection;
use yii\rbac\{BaseManager,Item,Role};
use yii\console\Controller;
use santilin\churros\helpers\{AppHelper,AuthHelper};

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
	public function createModelsPermissions(array $model_classes, string $viewer_desc = 'viewer', string $editor_desc = 'editor')
	{
		$auth = $this->authManager;
		$viewer = AuthHelper::createOrUpdateRole($viewer_desc,
			Yii::t('churros', "View all"), $auth);
		AuthHelper::echoLastMessage();
		$editor = AuthHelper::createOrUpdateRole($editor_desc,
			Yii::t('churros', "Edit all"), $auth);
		foreach ($model_classes as $model_class) {
			$this->createModelPermissions($model_class, $viewer, $editor);
		}
	}


	/**
	 * Creates the permissions for a model inside a module
	 */
	protected function createModelPermissions(string $model_class, Role $viewer, Role $editor)
	{
		$auth = $this->authManager;
		$model = $model_class::instance();
		$model_title = $model->t('app', "{title_plural}");
		$model_perm_name = AppHelper::lastWord($model_class, '\\');

		$model_editor = AuthHelper::createOrUpdateRole(
			$model_perm_name . '.' . $editor->name,
			Yii::t('churros', '{model} editor', ['model' => $model_title]), $auth);
		AuthHelper::echoLastMessage();
		$model_viewer = AuthHelper::createOrUpdateRole(
			$model_perm_name . '.' . $viewer->name,
			Yii::t('churros', '{model} viewer', ['model' => $model_title]), $auth);
		AuthHelper::echoLastMessage();

		if (!$auth->hasChild($viewer, $model_viewer)) {
			$auth->addChild($viewer, $model_viewer);
			echo "+ Role '{$model_viewer->name}' added to role '{$viewer->name}'\n";
		} else {
			echo "= Role '{$model_viewer->name}' already exists in role {$viewer->name}\n";
		}
		if (!$auth->hasChild($editor, $model_editor)) {
			$auth->addChild($editor, $model_editor);
			echo "+ Role '{$model_editor->name}' added to role '{$editor->name}'\n";
		} else {
			echo "= Role '{$model_editor->name}' already exists in role {$editor->name}\n";
		}

		foreach (['index','view','create','update','duplicate','delete','print'] as $perm_name) {
			$perm_desc = Yii::t('churros', ucfirst($perm_name));
			$permission = AuthHelper::createOrUpdatePermission(
				$model_perm_name . ".$perm_name",
				$perm_desc . ' ' . $model_title, $auth);
			AuthHelper::echoLastMessage();

			$add_to_visora = ($perm_name == 'view' || $perm_name == 'index' || $perm_name == 'menu' );
			if ($add_to_visora) {
				if (!$auth->hasChild($model_viewer, $permission)) {
					$auth->addChild($model_viewer, $permission);
					echo "+ Permission '{$permission->name}' added to role '{$model_viewer->name}'\n";
				} else {
					echo "= Permission '{$permission->name}' already exists in role {$model_viewer->name}\n";
				}
			}
			if (!$auth->hasChild($model_editor, $permission)) {
				$auth->addChild($model_editor, $permission);
				echo "+ Permission '{$permission->name}' added to role '{$model_editor->name}'\n";
			} else {
				echo "= Permission '{$permission->name}' already exists in role '{$model_editor->name}'\n";
			}
		}
	}

	/**
	 * Creates the permissions for a model inside a module
	 */
	public function createControllerPermissions(string $module_id, string $model_name,
		array $controller, Role $viewer, Role $editor, Role $deleter)
	{
		$model_class = $controller['class'];
		if (!class_exists($model_class)) {
			return;
		}
		$auth = $this->authManager;
		$model = $model_class::instance();
		$model_title = $model->t('app', "{title_plural}");
		$model_perm_name = $module_id . '.' . $model_name;

		$model_editor = AuthHelper::createOrUpdateRole(
			str_replace('.', ".{$model_name}.", $editor->name),
			Yii::t('churros', '{model} editor', ['model' => $model_title]), $auth);
		AuthHelper::echoLastMessage();
		$model_viewer = AuthHelper::createOrUpdateRole(
			str_replace('.', ".{$model_name}.", $viewer->name),
			Yii::t('churros', '{model} viewer', ['model' => $model_title]), $auth);
		AuthHelper::echoLastMessage();
		$model_deleter = AuthHelper::createOrUpdateRole(
			str_replace('.', ".{$model_name}.", $deleter->name),
			Yii::t('churros', '{model} deleter', ['model' => $model_title]), $auth);
		AuthHelper::echoLastMessage();

		if (!$auth->hasChild($viewer, $model_viewer)) {
			$auth->addChild($viewer, $model_viewer);
			echo "+ Role '{$model_viewer->name}' added to role '{$viewer->name}'\n";
		} else {
			echo "= Role '{$model_viewer->name}' already exists in role {$viewer->name}\n";
		}
		if (!$auth->hasChild($editor, $model_editor)) {
			$auth->addChild($editor, $model_editor);
			echo "+ Role '{$model_editor->name}' added to role '{$editor->name}'\n";
		} else {
			echo "= Role '{$model_editor->name}' already exists in role {$editor->name}\n";
		}
		if (!$auth->hasChild($deleter, $model_deleter)) {
			$auth->addChild($deleter, $model_deleter);
			echo "+ Role '{$model_deleter->name}' added to role '{$deleter->name}'\n";
		} else {
			echo "= Role '{$model_deleter->name}' already exists in role {$deleter->name}\n";
		}

		foreach ($controller['perms'] as $perm_name) {
			$perm_desc = Yii::t('churros', ucfirst($perm_name));
			$permission = AuthHelper::createOrUpdatePermission(
				$model_perm_name . "." . lcFirst($perm_name),
				$perm_desc . ' ' . $model_title, $auth);
			AuthHelper::echoLastMessage();

			$add_to_visora = ($perm_name == 'view' || $perm_name == 'index' || $perm_name == 'menu');
			if ($add_to_visora) {
				if (!$auth->hasChild($model_viewer, $permission)) {
					$auth->addChild($model_viewer, $permission);
					echo "+ Permission '{$permission->name}' added to role '{$model_viewer->name}'\n";
 				} else {
 					echo "= Permission '{$permission->name}' already exists in role {$model_viewer->name}\n";
				}
			} else if ($perm_name == 'delete') {
				if (!$auth->hasChild($model_deleter, $permission)) {
					$auth->addChild($model_deleter, $permission);
					echo "+ Permission '{$permission->name}' added to role '{$model_deleter->name}'\n";
				} else {
					echo "= Permission '{$permission->name}' already exists in role {$model_deleter->name}\n";
				}
			} else {
				if (!$auth->hasChild($model_editor, $permission)) {
					$auth->addChild($model_editor, $permission);
					echo "+ Permission '{$permission->name}' added to role '{$model_editor->name}'\n";
				} else {
					echo "= Permission '{$permission->name}' already exists in role '{$model_editor->name}'\n";
				}
			}
		}
	}

	/**
	 * Creates the permissions for a rbac module
	 */
	public function createModuleRbacPermissions(string $module_id, array $module_info)
	{
		$auth = $this->authManager;
		$module_desc = $module_info['title']??$module_id;
		$viewer = AuthHelper::createOrUpdateRole("$module_id.viewer",
			Yii::t('churros', '{perm_desc} module viewer', ['perm_desc' => $module_desc]), $auth);
		AuthHelper::echoLastMessage();
		$editor = AuthHelper::createOrUpdateRole("$module_id.editor",
			Yii::t('churros', '{perm_desc} module editor', ['perm_desc' => $module_desc]), $auth);
		AuthHelper::echoLastMessage();
		$deleter = AuthHelper::createOrUpdateRole("$module_id.deleter",
			Yii::t('churros', '{perm_desc} module deleter', ['perm_desc' => $module_desc]), $auth);
		AuthHelper::echoLastMessage();
		$admin = AuthHelper::createOrUpdateRole("$module_id.admin",
			Yii::t('churros', '{perm_desc} module admin', ['perm_desc' => $module_desc]), $auth);
		AuthHelper::echoLastMessage();
		foreach ($module_info['controllers']??[] as $cname => $controller) {
			$this->createControllerPermissions($module_id, $cname, $controller, $viewer, $editor, $deleter);
			$module_desc = $cname . ' in ' . $module_info['title']??$module_id;
			$permission = AuthHelper::createOrUpdatePermission(
				"{$module_id}.{$cname}.menu", Yii::t('churros', '{perm_desc} controller menu', ['perm_desc' => $module_desc]), $auth);
			AuthHelper::echoLastMessage();
		}
	}

	/**
	 * Lists all permissions, optionally by type
	 */
	public function actionListAll($type = null)
	{
		$no_model_perms = [];
		$prev_model = null;
		if ($type == null || StringHelper::startsWith($type, 'perm')) {
			$perms = $this->authManager->getItems(Item::TYPE_PERMISSION);
			asort($perms);
			$this->stdout("= PERMISSIONS\n");
			foreach ($perms as $perm) {
				$name = $perm->name;
				if (preg_match( '/([A-Za-z_][A-Za-z_0-9]*).(index|create|view|update|delete|update|report|duplicate)/', $name, $m )) {
					if ($m[1] == "Reports") {
						continue;
					}
					if ($prev_model == $m[1]) {
						$this->stdout(', ' . $m[2]);
					} else {
						if ($prev_model == null) {
							$this->stdout("== MODELS ==\n");
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
			if ($prev_model) {
				$this->stdout("\n");
			}
			$this->stdout("== OTHER\n");
			foreach( $no_model_perms as $perm) {
				$this->stdout($perm->name . "\n");
			}
		}
		if ($type == null || StringHelper::startsWith($type, 'rol')) {
			$roles = $this->authManager->getItems(Item::TYPE_ROLE);
			asort($roles);
			$this->stdout("\n= ROLES\n");
			foreach( $roles as $role) {
				$subroles = $this->authManager->getChildRoles($role->name);
				if (count($subroles)) {
					$s_subroles = '';
					foreach($subroles as $subrol) {
						if ($subrol->name != $role->name) {
							$s_subroles .= $subrol->name . ", ";
						}
					}
					if ($s_subroles) {
						$this->stdout("- ".$role->name.":roles:$s_subroles\n");
					}
				}
				$role_perms = $this->authManager->getPermissionsByRole($role->name);
				if (count($role_perms)) {
					$this->stdout("- ".$role->name.":perms:");
					foreach($role_perms as $perm) {
						$this->stdout($perm->name . ", ");
					}
					$this->stdout("\n");
				} else if (empty($s_subroles)) {
					$this->stdout("- ". $role->name. "\n");
				}
			}
		}

		if ($type == null || StringHelper::startsWith($type, 'user')) {
			$this->stdout("\n= USERS' ASSIGNMENTS\n");
			$user_class = Yii::$app->user->identityClass;
			$user = new $user_class;
			$users = $user->find()->all();
			foreach( $users as $user) {
				$this->stdout("user:{$user->id}:{$user->username}:");
				$assignments = $this->authManager->getAssignments($user->id);
				foreach( $assignments as $as) {
					$this->stdout($as->roleName . ", ");
				}
				$this->stdout("\n");
			}
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
		if (count($subroles)) {
			$s_subroles = '';
			foreach($subroles as $subrol) {
				if ($subrol->name != $role) {
					$s_subroles .= $subrol->name . ", ";
				}
			}
			if ($s_subroles) {
				$this->stdout("- ".$role.":roles:$s_subroles\n");
			}
		}
		$role_perms = $this->authManager->getPermissionsByRole($role);
		if (count($role_perms)) {
			$this->stdout("- ".$role.":perms:");
			foreach($role_perms as $perm) {
				$this->stdout($perm->name . ", ");
			}
			$this->stdout("\n");
		} else if (empty($s_subroles)) {
			$this->stdout("- ". $role. "\n");
		}
	}


	public function actionAssignPermToUser($perm_name, $user_id)
	{
		$permission = $this->authManager->getItem($perm_name);
		if ($permission == null) {
			return false;
		}
		$this->authManager->assign($permission, $user_id);
	}

	public function actionAssignToRole($perm_name, $role_name)
	{
		$permission = $this->authManager->getItem($perm_name);
		if ($permission == null) {
			throw new \Exception( "$perm_name: perm not found" );
		}
		$role = $this->authManager->getRole($role_name);
		if (!$role) {
			throw new \Exception( "$role_name: role not found" );
		}
		if (!$this->authManager->hasChild($role, $permission)) {
			$this->authManager->addChild($role, $permission);
			AuthHelper::echoLastMessage();
		}
	}

	public function actionCreatePermission($perm_name, $perm_desc)
	{
		$permission = AuthHelper::createOrUpdatePermission(
			$perm_name, $perm_desc, $this->authManager);
		AuthHelper::echoLastMessage();
	}

	public function actionCreateRole($perm_name, $perm_desc)
	{
		$permission = AuthHelper::createOrUpdateRole(
			$perm_name, $perm_desc, $this->authManager);
		AuthHelper::echoLastMessage();
	}

	public function actionRemovePermFromRole($perm_name, $role_name)
	{
		AuthHelper::removePermFromRole($role_name, $perm_name, $this->authManager);
		AuthHelper::echoLastMessage();
	}

	public function actionRemoveRoles(array|string $role_name): void
	{
		AuthHelper::removeRoles($role_name, $this->authManager);
		AuthHelper::echoLastMessage();
	}

	public function actionRemovePermissions(array|string $role_name): void
	{
		AuthHelper::removeRoles($role_name, $this->authManager);
		AuthHelper::echoLastMessage();
	}

	public function actionRemoveAll()
	{
		$this->authManager->removeAll();
		AuthHelper::echoLastMessage();
	}


	// Display roles and their permissions recursively
	protected function rolesTree(array $roles, string $pre, $authManager)
	{
		foreach ($roles as $role) {
			if ($pre == '') {
				$this->stdout("+ Role: " . $role->name . "\n", Console::FG_YELLOW);
			}

			foreach ($authManager->getChildRoles($role->name) as $child_role) {
				if ($child_role->name == $role->name) {
					continue;
				}
				$this->stdout("$pre  └─ Role: " . $child_role->name . "\n", Console::FG_YELLOW);
				$this->rolesTree([$child_role], "  $pre", $authManager);
			}
			foreach ($authManager->getPermissionsByRole($role->name) as $child_perm) {
				$this->stdout("$pre  └─ Permission: " . $child_perm->name . "\n", Console::FG_GREEN);
			}
		}
	}

	public function actionListTree()
	{
		$authManager = Yii::$app->authManager;

		// Get all roles and permissions
		$roles = $authManager->getRoles();
		$this->stdout("\nRoles:\n", Console::FG_YELLOW);
		$this->rolesTree($roles, '', $authManager);

		// Display standalone permissions
		$permissions = $authManager->getPermissions();
		$this->stdout("\nStandalone Permissions:\n", Console::FG_YELLOW);
		foreach ($permissions as $permission) {
			if (!isset($roles[$permission->name])) {
				$this->stdout("  └─ Permission: " . $permission->name . "\n", Console::FG_GREEN);
			}
		}
	}


} // class

