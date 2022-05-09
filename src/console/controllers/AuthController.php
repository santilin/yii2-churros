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
		$msg = '';
		$visora = AuthHelper::createOrUpdateRole('app.visor',
			Yii::t('churros', 'Ver todos los ficheros'), $msg, $auth);
		if( $msg != '' ) echo "$msg\n";
		$editora = AuthHelper::createOrUpdateRole('app.editor',
			Yii::t('churros', 'Editar todos los ficheros'), $msg, $auth);
		if( $msg != '' ) echo "$msg\n";

		$model = new $model_class;
		$model_title = $model->t('app', "{title_plural}");
		$model_editora = AuthHelper::createOrUpdateRole(
			$model_name . '.editor',
			Yii::t('churros', 'Editor de {model}', ['model' => $model_title]), $msg, $auth);
		if( $msg != '' ) echo "$msg\n";
		$model_visora = AuthHelper::createOrUpdateRole(
			$model_name . '.visor',
			Yii::t('churros', 'Visor de {model}', ['model' => $model_title]), $msg, $auth);
		if( $msg != '' ) echo "$msg\n";
		$model_editora_own = AuthHelper::createOrUpdateRole(
			$model_name . '.editor.own',
			Yii::t('churros', 'Editor de sus {model}', ['model' => $model_title]), $msg, $auth);
		if( $msg != '' ) echo "$msg\n";
		$model_visora_own = AuthHelper::createOrUpdateRole(
			$model_name . '.visor.own',
			Yii::t('churros', 'Visor de sus {model}', ['model' => $model_title]), $msg, $auth);
		if( $msg != '' ) echo "$msg\n";

		foreach( [ 'create' => Yii::t('churros', 'Crear'),
					'view' => Yii::t('churros', 'Ver'),
					'edit' => Yii::t('churros', 'Editar'),
					'delete' => Yii::t('churros', 'Borrar'),
					'index' => Yii::t('churros', 'Listar')
				  ] as $perm_name => $perm_desc) {
			$permission = AuthHelper::createOrUpdatePermission(
				$model_name . ".$perm_name",
				$perm_desc . ' ' . $model_title, $msg, $auth);
			if( $msg != '' ) echo "$msg\n";
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
			$permission_own = AuthHelper::createOrUpdatePermission(
				$model_name . ".$perm_name.own",
				$perm_desc .' ' . $model->t('churros', "sus propi{-as} {title_plural}"), $msg, $auth);
			if ($msg != '' ) echo "$msg\n";;
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

	public function createModuleModelPermissions($module_name, $model_name, $model_class)
	{
		$auth = $this->authManager;

		$model = new $model_class;
		$model_title = $model->t('app', "{title_plural}");

		$role_name = "module.$module_name.menu.all";
		$role_all = AuthHelper::createOrUpdateRole($role_name,
			Yii::t('churros', "Acceso a todos los modelos del módulo $module_name"),
			$msg, $auth);
		if ($msg != '' ) echo "$msg\n";

		$perm_name = "module.$module_name.menu.$model_name";
		$permission = AuthHelper::createOrUpdatePermission($perm_name,
			Yii::t('churros', "Acceso al menú de $model_title del módulo $module_name"),
			$msg, $auth);
		if ($msg != '' ) echo "$msg\n";
		if( !$auth->hasChild($role_all, $permission) ) {
			$auth->addChild($role_all, $permission);
			echo "permission {$permission->name} added to role {$role_all->name}\n";
		}
	}

	/**
	 * Creates the permissions for a module
	 */
	public function createModulePermissions($module_name)
	{
		$msg = null;
		AuthHelper::createOrUpdatePermission("module.$module_name.menu",
			Yii::t('churros', "Acceso al menu del módulo $module_name"),
			$msg, $this->authManager);
		if ($msg != '' ) echo "$msg\n";
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

	public function actionRemoveAll()
	{
		$this->authManager->removeAll();
	}

} // class

