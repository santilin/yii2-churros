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
use yii\rbac\{BaseManager,DbManager,Item,Role};
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
	/** @var bool Verbose output */
	public bool $verbose = false;
	/**
	 * @var bool Fuerza el borrado de roles/permisos aunque los tenga alguna
	 * usuaria asignados (--force=1). Sin force, los items en uso se conservan.
	 */
	public bool $force = false;
	/** @var string Output format for list-role: 'simple' or 'details' */
	public string $format = 'simple';

    public function options($actionID)
    {
        $options = ['verbose', 'force'];
        if ($actionID === 'list-role') {
            $options[] = 'format';
        }
        return array_merge(
            parent::options($actionID),
            $options
        );
    }

    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'f' => 'format',
			'v' => 'verbose',
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
	public function createControllerPermissions(string $module_id, string $module_desc,
		string $model_name, array $controller,
		?Role $viewer, ?Role $creator, ?Role $editor, ?Role $full_editor,
		?Role $deleter, ?Role $granter, ?Role $admin, array &$all_items = [])
	{
		$model_class = $controller['class'];
		if (!class_exists($model_class)) {
			return;
		}
		$auth = $this->authManager;
		$model = $model_class::instance();
		$model_title = $model->t('app', "{Title_plural}");

		$model_viewer = $model_creator = $model_editor = $model_full_editor
			= $model_deleter = $model_granter = $model_admin = null;
		// Create model roles
		if ($viewer) {
			$model_viewer = AuthHelper::createOrUpdateRole(
				str_replace('.', ".{$model_name}.", $viewer->name),
				Yii::t('churros', '{module}: {model}: visor/a', [
					'module' => $module_desc, 'model' => $model_title
				]), true, $auth);
			unset($all_items[$model_viewer->name]);
			AuthHelper::flushMessages($this->verbose);
			if (!$auth->hasChild($viewer, $model_viewer)) {
				$auth->addChild($viewer, $model_viewer);
				echo "+ Role '{$model_viewer->name}' added to role '{$viewer->name}'\n";
			} elseif ($this->verbose) {
				echo "= Role '{$model_viewer->name}' already exists in role {$viewer->name}\n";
			}
		}
		if ($creator) {
			$model_creator = AuthHelper::createOrUpdateRole(
				str_replace('.', ".{$model_name}.", $creator->name),
				Yii::t('churros', '{module}: {model}: creador/a', [
					'module' => $module_desc, 'model' => $model_title
				]), true, $auth);
			unset($all_items[$model_creator->name]);
			AuthHelper::flushMessages($this->verbose);
			if (!$auth->hasChild($creator, $model_creator)) {
				$auth->addChild($creator, $model_creator);
				echo "+ Role '{$model_creator->name}' added to role '{$creator->name}'\n";
			} elseif ($this->verbose) {
				echo "= Role '{$model_creator->name}' already exists in role {$creator->name}\n";
			}
		}
		if ($deleter) {
			$model_deleter = AuthHelper::createOrUpdateRole(
				str_replace('.', ".{$model_name}.", $deleter->name),
				Yii::t('churros', '{module}: {model}: eliminador/a', [
					'module' => $module_desc, 'model' => $model_title
				]), true, $auth);
			unset($all_items[$model_deleter->name]);
			AuthHelper::flushMessages($this->verbose);
			if (!$auth->hasChild($deleter, $model_deleter)) {
				$auth->addChild($deleter, $model_deleter);
				echo "+ Role '{$model_deleter->name}' added to role '{$deleter->name}'\n";
			} elseif ($this->verbose) {
				echo "= Role '{$model_deleter->name}' already exists in role {$deleter->name}\n";
			}
		}
		if ($editor) {
			$model_editor = AuthHelper::createOrUpdateRole(
				str_replace('.', ".{$model_name}.", $editor->name),
				Yii::t('churros', '{module}: {model}: editor/a', [
					'module' => $module_desc, 'model' => $model_title
				]), true, $auth);
			unset($all_items[$model_editor->name]);
			AuthHelper::flushMessages($this->verbose);
			if (!$auth->hasChild($editor, $model_editor)) {
				$auth->addChild($editor, $model_editor);
				echo "+ Role '{$model_editor->name}' added to role '{$editor->name}'\n";
			} elseif ($this->verbose) {
				echo "= Role '{$model_editor->name}' already exists in role {$editor->name}\n";
			}
		}
		if ($granter) {
			$model_granter = AuthHelper::createOrUpdateRole(
				str_replace('.', ".{$model_name}.", $granter->name),
				Yii::t('churros', '{module}: {model}: asignador/a de privilegios', [
					'module' => $module_desc, 'model' => $model_title
				]), true, $auth);
			unset($all_items[$model_granter->name]);
			AuthHelper::flushMessages($this->verbose);
			if (!$auth->hasChild($granter, $model_granter)) {
				$auth->addChild($granter, $model_granter);
				echo "+ Role '{$model_granter->name}' added to role '{$granter->name}'\n";
			} elseif ($this->verbose) {
				echo "= Role '{$model_granter->name}' already exists in role {$granter->name}\n";
			}
		}
		if ($full_editor) {
			$model_full_editor = AuthHelper::createOrUpdateRole(
				str_replace('.', ".{$model_name}.", $full_editor->name),
				Yii::t('churros', '{module}: {model}: editor/a total', [
					'module' => $module_desc, 'model' => $model_title
				]), true, $auth);
			unset($all_items[$model_full_editor->name]);
			AuthHelper::flushMessages($this->verbose);
			if (!$auth->hasChild($full_editor, $model_full_editor)) {
				$auth->addChild($full_editor, $model_full_editor);
				echo "+ Role '{$model_full_editor->name}' added to role '{$full_editor->name}'\n";
			} elseif ($this->verbose) {
				echo "= Role '{$model_full_editor->name}' already exists in role {$full_editor->name}\n";
			}
		}
		if ($admin) {
			$model_admin = AuthHelper::createOrUpdateRole(
				str_replace('.', ".{$model_name}.", $admin->name),
				Yii::t('churros', '{module}: {model}: administrador/a', [
					'module' => $module_desc, 'model' => $model_title
				]), true, $auth);
			unset($all_items[$model_admin->name]);
			AuthHelper::flushMessages($this->verbose);
			if (!$auth->hasChild($admin, $model_admin)) {
				$auth->addChild($admin, $model_admin);
				echo "+ Role '{$model_admin->name}' added to role '{$admin->name}'\n";
			} elseif ($this->verbose) {
				echo "= Role '{$model_admin->name}' already exists in role {$admin->name}\n";
			}
			if ($full_editor) {
				if (!$auth->hasChild($model_admin, $model_full_editor)) {
					$auth->addChild($model_admin, $model_full_editor);
					echo "+ Role '{$model_full_editor->name}' added to role '{$model_admin->name}'\n";
				} elseif ($this->verbose) {
					echo "= Role '{$model_full_editor->name}' already exists in role {$model_admin->name}\n";
				}
			}
			if ($editor) {
				if (!$auth->hasChild($model_admin, $model_editor)) {
					$auth->addChild($model_admin, $model_editor);
					echo "+ Role '{$model_editor->name}' added to role '{$model_admin->name}'\n";
				} elseif ($this->verbose) {
					echo "= Role '{$model_editor->name}' already exists in role {$model_admin->name}\n";
				}
			}
			if ($creator) {
				if (!$auth->hasChild($model_admin, $model_creator)) {
					$auth->addChild($model_admin, $model_creator);
					echo "+ Role '{$model_creator->name}' added to role '{$model_admin->name}'\n";
				} elseif ($this->verbose) {
					echo "= Role '{$model_creator->name}' already exists in role {$model_admin->name}\n";
				}
			}
			if ($deleter) {
				if (!$auth->hasChild($model_admin, $model_deleter)) {
					$auth->addChild($model_admin, $model_deleter);
					echo "+ Role '{$model_deleter->name}' added to role '{$model_admin->name}'\n";
				} elseif ($this->verbose) {
					echo "= Role '{$model_deleter->name}' already exists in role {$model_admin->name}\n";
				}
			}
			if ($viewer) {
				if (!$auth->hasChild($model_admin, $model_viewer)) {
					$auth->addChild($model_admin, $model_viewer);
					echo "+ Role '{$model_viewer->name}' added to role '{$model_admin->name}'\n";
				} elseif ($this->verbose) {
					echo "= Role '{$model_viewer->name}' already exists in role {$model_admin->name}\n";
				}
			}
			if ($granter) {
				if (!$auth->hasChild($model_admin, $model_granter)) {
					$auth->addChild($model_admin, $model_granter);
					echo "+ Role '{$model_granter->name}' added to role '{$model_admin->name}'\n";
				} elseif ($this->verbose) {
					echo "= Role '{$model_granter->name}' already exists in role {$model_admin->name}\n";
				}
			}
		}

		if ($full_editor) {
			if ($creator) {
				if (!$auth->hasChild($model_full_editor, $model_creator)) {
					$auth->addChild($model_full_editor, $model_creator);
					echo "+ Role '{$model_creator->name}' added to role '{$model_full_editor->name}'\n";
				} elseif ($this->verbose) {
					echo "= Role '{$model_creator->name}' already exists in role {$model_full_editor->name}\n";
				}
			}
			if ($deleter) {
				if (!$auth->hasChild($model_full_editor, $model_deleter)) {
					$auth->addChild($model_full_editor, $model_deleter);
					echo "+ Role '{$model_deleter->name}' added to role '{$model_full_editor->name}'\n";
				} elseif ($this->verbose) {
					echo "= Role '{$model_deleter->name}' already exists in role {$model_full_editor->name}\n";
				}
			}
			if ($editor) {
				if (!$auth->hasChild($model_full_editor, $model_editor)) {
					$auth->addChild($model_full_editor, $model_editor);
					echo "+ Role '{$model_editor->name}' added to role '{$model_full_editor->name}'\n";
				} elseif ($this->verbose) {
					echo "= Role '{$model_editor->name}' already exists in role {$model_full_editor->name}\n";
				}
			}
		}

		if ($editor) {
			if ($viewer) {
				if (!$auth->hasChild($model_editor, $model_viewer)) {
					$auth->addChild($model_editor, $model_viewer);
					echo "+ Role '{$model_viewer->name}' added to role '{$model_editor->name}'\n";
				} elseif ($this->verbose) {
					echo "= Role '{$model_viewer->name}' already exists in role {$model_editor->name}\n";
				}
			}
		}

		if ($deleter) {
			if ($viewer) {
				if (!$auth->hasChild($model_deleter, $model_viewer)) {
					$auth->addChild($model_deleter, $model_viewer);
					echo "+ Role '{$model_viewer->name}' added to role '{$model_deleter->name}'\n";
				} elseif ($this->verbose) {
					echo "= Role '{$model_viewer->name}' already exists in role {$model_deleter->name}\n";
				}
			}
		}

		if ($creator) {
			if ($viewer) {
				if (!$auth->hasChild($model_creator, $model_viewer)) {
					$auth->addChild($model_creator, $model_viewer);
					echo "+ Role '{$model_viewer->name}' added to role '{$model_creator->name}'\n";
				} elseif ($this->verbose) {
					echo "= Role '{$model_viewer->name}' already exists in role {$model_creator->name}\n";
				}
			}
		}

		$model_perm_name = $module_id . '.' . $model_name;
		foreach ($controller['perms'] as $perm_name) {
			$perm_name = mb_lcfirst($perm_name);
			$perm_desc = Yii::t('churros', '{module}: {model}: {perm}', [
				'module' => $module_desc,
				'model' => $model_title,
				'perm' => Yii::t('churros', $perm_name)]);
			$permission = AuthHelper::createOrUpdatePermission(
				$model_perm_name . "." . $perm_name, $perm_desc, true, $auth);
			unset($all_items[$permission->name]);
			AuthHelper::flushMessages($this->verbose);
			$roles_to_add = [];
			switch ($perm_name) {
				case 'view':
				case 'index':
				case 'informes':
					$roles_to_add = [ $model_viewer, $model_editor, $model_full_editor, $model_admin ];
					break;
				case 'delete':
					$roles_to_add = [ $model_deleter, $model_full_editor, $model_admin ];
					break;
				case 'create':
				case 'duplicate':
					$roles_to_add = [ $model_creator, $model_editor, $model_full_editor, $model_admin ];
					break;
				case 'update':
					$roles_to_add = [ $model_editor, $model_full_editor, $model_admin];
					break;
				default:
					$roles_to_add = [ $model_admin ];
					break;
			}
			foreach (array_filter($roles_to_add) as $role_to_add) {
				if (!$auth->hasChild($role_to_add, $permission)) {
					$auth->addChild($role_to_add, $permission);
					echo "+ Permission '{$permission->name}' added to role '{$role_to_add->name}'\n";
				} elseif ($this->verbose) {
					echo "= Permission '{$permission->name}' already exists in role {$role_to_add->name}\n";
				}
				break; // only the first one
			}
		}

		$module_access_role = AuthHelper::createOrUpdateRole(
			$module_id,
			Yii::t('churros', '{module}: acceso al módulo', [
				'module' => $module_desc
			]), true, $auth);
		AuthHelper::flushMessages($this->verbose);
		$module_access_permission = AuthHelper::createOrUpdatePermission(
			$module_id . ".index",
				Yii::t('churros', '{module}: acceso al inicio del módulo', [
					'module' => $module_desc
				]), true, $auth);
		unset($all_items[$module_access_permission->name]);

		// if (!$auth->hasChild($module_access_role, $module_access_permission)) {
		// 	$auth->addChild($module_access_role, $module_access_permission);
		// 	echo "+ Permission '{$module_access_permission->name}' added to role '{$module_access_role->name}'\n";
		// } elseif ($this->verbose) {
		// 	echo "= Permission '{$module_access_permission->name}' already exists in role {$module_access_role->name}\n";
		// }

		foreach (array_filter([$viewer, $editor,]) as $role_to_add_to) {
			if (!$auth->hasChild($role_to_add_to, $module_access_permission)) {
				$auth->addChild($role_to_add_to, $module_access_permission);
				echo "+ Role '{$module_access_permission->name}' added to role '{$role_to_add_to->name}'\n";
			} elseif ($this->verbose) {
				echo "= Role '{$module_access_permission->name}' already exists in role {$role_to_add_to->name}\n";
			}
		// 	if (!$auth->hasChild($module_access_role, $role_to_add)) {
		// 		$auth->addChild($module_access_role, $role_to_add);
		// 		echo "+ Role '{$role_to_add->name}' added to role '{$module_access_role->name}'\n";
		// 	} elseif ($this->verbose) {
		// 		echo "= Role '{$role_to_add->name}' already exists in role {$$module_access_role->name}\n";
		// 	}
		// 	break; // only the first one
		}
	}

	/**
	 * Crea los permisos de `web_controllers` (controladores web planos, sin
	 * modelo): capel genera un permiso por acción ahí igual que hace con los 6
	 * estándar de `controllers` (mismo `ControllerActionDefinition::permissions()`
	 * por debajo). A diferencia de createControllerPermissions(), no hay
	 * jerarquía viewer/creator/editor que montar — un controlador web no tiene
	 * convención de permisos propia —, así que cada permiso se cuelga
	 * directamente de $admin; si una acción concreta necesita otro rol, se
	 * reasigna a mano (como ya se hacía antes de que esto se generara solo).
	 *
	 * Un controlador con `access_filters: module` no reparte por acción —
	 * `ModuleRbacAccessRule` para ese caso comprueba el permiso de controlador
	 * entero `<modulo>.<Controlador>`, no uno por acción— así que ahí se crea un
	 * único permiso en vez de uno por cada entrada de `perms`.
	 *
	 * @param array $web_controllers `Capel::MODULES[$module_id]['web_controllers']`:
	 *   nombre de controlador -> ['perms' => [nombres de acción], 'access_filters' => [...]]
	 */
	public function createWebControllerPermissions(string $module_id, string $module_desc,
		array $web_controllers, ?Role $admin, array &$all_items = [])
	{
		$auth = $this->authManager;
		foreach ($web_controllers as $cname => $wc) {
			// access_filters es el propio del controlador o, si no tiene, el heredado
			// del módulo (ver ModuleControllerDefinition::accessFilters() en capel).
			$access_filters = $wc['access_filters'] ?? [];
			if (in_array('module', $access_filters, true)) {
				$permission = AuthHelper::createOrUpdatePermission(
					"$module_id.$cname",
					Yii::t('churros', '{module}: {controller}: acceso completo', [
						'module' => $module_desc, 'controller' => $cname,
					]), true, $auth);
				unset($all_items[$permission->name]);
				AuthHelper::flushMessages($this->verbose);
				if ($admin) {
					if (!$auth->hasChild($admin, $permission)) {
						$auth->addChild($admin, $permission);
						echo "+ Permission '{$permission->name}' added to role '{$admin->name}'\n";
					} elseif ($this->verbose) {
						echo "= Permission '{$permission->name}' already exists in role {$admin->name}\n";
					}
				}
				continue;
			}
			// sin 'rbac' ahí (y tampoco 'module', ya tratado arriba), el acceso no
			// pasa por permisos (admin/logged/username/...) y no hay nada que crear.
			if (!in_array('rbac', $access_filters, true)) {
				continue;
			}
			foreach ($wc['perms'] ?? [] as $perm_name) {
				// `<modulo>.index` ya lo crea createModuleRbacPermissions() (es el
				// permiso genérico de "llegar a la portada del módulo"); un `index`
				// propio de este controlador sería redundante y confuso con ese.
				if ($perm_name === 'index') {
					continue;
				}
				$perm_desc = Yii::t('churros', '{module}: {controller}: {perm}', [
					'module' => $module_desc,
					'controller' => $cname,
					'perm' => $perm_name,
				]);
				// <modulo>.<Controlador>.<permiso>, igual que ModuleRbacAccessRule::
				// matchCrudAction() para CRUD y que los permisos de Ceuta ya existentes
				// (participantes.Ceuta.actuaciones, concedidos a mano hasta ahora).
				$permission = AuthHelper::createOrUpdatePermission(
					"$module_id.$cname.$perm_name", $perm_desc, true, $auth);
				unset($all_items[$permission->name]);
				AuthHelper::flushMessages($this->verbose);
				if ($admin) {
					if (!$auth->hasChild($admin, $permission)) {
						$auth->addChild($admin, $permission);
						echo "+ Permission '{$permission->name}' added to role '{$admin->name}'\n";
					} elseif ($this->verbose) {
						echo "= Permission '{$permission->name}' already exists in role {$admin->name}\n";
					}
				}
			}
		}
	}

	/**
	 * Creates the permissions for a rbac module and shows the ones not used
	 *
	 * @param bool $deleteUnused si es true, en vez de solo listar los items no
	 * usados (createdAt=0 y no regenerados en esta pasada) los borra -- pero
	 * solo los que isItemInUse() diga que nadie tiene asignados, ni directa
	 * ni heredado por un rol intermedio.
	 */
	public function createModuleRbacPermissions(string $module_id, array $module_info,
		array $roles_to_create = [ 'viewer', 'creator', 'editor', 'full-editor', 'deleter', 'granter', 'admin' ],
		bool $deleteUnused = false)
	{
		$auth = $this->authManager;
		// keeps track of all module rules to keep default ones: createdAt=0
		// marca los items que creó esta misma generación automática (ver
		// AuthHelper::createOrUpdateRole()/createOrUpdatePermission() con
		// $is_default=true); uno creado a mano (actionIndex(), permisosCeuta()...)
		// tiene un timestamp real y no hay que listarlo aquí aunque esta pasada
		// no lo toque, porque nunca es su trabajo tocarlo.
		$all_items = [];
		foreach ($this->authManager->getRoles() as $role) {
			if (StringHelper::startsWith($role->name, "$module_id.") && (int) $role->createdAt === 0) {
				$all_items[$role->name] = true;
			}
		}
		foreach ($this->authManager->getPermissions() as $perm) {
			if (StringHelper::startsWith($perm->name, "$module_id.") && (int) $perm->createdAt === 0) {
				$all_items[$perm->name] = true;
			}
		}

		$module_desc = mb_ucfirst($module_info['title'] ?? $module_id);
		if (in_array('viewer', $roles_to_create)) {
			$viewer = AuthHelper::createOrUpdateRole("$module_id.viewer",
				Yii::t('churros', '{module}:  visor/a ', ['module' => $module_desc]), true, $auth);
			unset($all_items[$viewer->name]);
			AuthHelper::flushMessages($this->verbose);
		} else {
			$viewer = null;
		}
		if (in_array('creator', $roles_to_create)) {
			$creator = AuthHelper::createOrUpdateRole("$module_id.creator",
				Yii::t('churros', '{module}:  creador/a', ['module' => $module_desc]), true, $auth);
			unset($all_items[$creator->name]);
			AuthHelper::flushMessages($this->verbose);
		} else {
			$creator = null;
		}
		if (in_array('editor', $roles_to_create)) {
			$editor = AuthHelper::createOrUpdateRole("$module_id.editor",
				Yii::t('churros', '{module}:  editor/a', ['module' => $module_desc]), true, $auth);
			unset($all_items[$editor->name]);
			AuthHelper::flushMessages($this->verbose);
		} else {
			$editor = null;
		}
		if (in_array('full-editor', $roles_to_create)) {
			$full_editor = AuthHelper::createOrUpdateRole("$module_id.full-editor",
				Yii::t('churros', '{module}:  editor/a total', ['module' => $module_desc]), true, $auth);
			unset($all_items[$full_editor->name]);
			AuthHelper::flushMessages($this->verbose);
		} else {
			$full_editor = null;
		}
		if (in_array('deleter', $roles_to_create)) {
			$deleter = AuthHelper::createOrUpdateRole("$module_id.deleter",
				Yii::t('churros', '{module}:  eliminador/a', ['module' => $module_desc]), true, $auth);
			unset($all_items[$deleter->name]);
			AuthHelper::flushMessages($this->verbose);
		} else {
			$deleter = null;
		}
		if (in_array('granter', $roles_to_create)) {
			$granter = AuthHelper::createOrUpdateRole("$module_id.granter",
				Yii::t('churros', '{module}:  asignador/a de privilegios', ['module' => $module_desc]), true, $auth);
			unset($all_items[$granter->name]);
			AuthHelper::flushMessages($this->verbose);
		} else {
			$granter = null;
		}
		if (in_array('admin', $roles_to_create)) {
			$admin = AuthHelper::createOrUpdateRole("$module_id.admin",
				Yii::t('churros', '{module}:  administrador/a', ['module' => $module_desc]), true, $auth);
			unset($all_items[$admin->name]);
			AuthHelper::flushMessages($this->verbose);
		} else {
			$admin = null;
		}

		foreach ($module_info['controllers']??[] as $cname => $controller) {
			$this->createControllerPermissions($module_id, $module_desc, $cname, $controller,
				$viewer, $creator, $editor, $full_editor, $deleter, $granter, $admin, $all_items);
			AuthHelper::flushMessages($this->verbose);
		}
		if (!empty($module_info['web_controllers'])) {
			$this->createWebControllerPermissions($module_id, $module_desc,
				$module_info['web_controllers'], $admin, $all_items);
			AuthHelper::flushMessages($this->verbose);
		}

		// list unused (or delete, with --deleteUnused=1)
		if (count($all_items)) {
			if ($deleteUnused) {
				foreach (array_keys($all_items) as $item_name) {
					$enUso = $this->isItemInUse($item_name);
					if ($enUso && empty($this->force)) {
						echo "! No se borra '$item_name': sigue asignada a alguna usuaria (directa o por un rol intermedio).\n";
						continue;
					}
					$item = $auth->getPermission($item_name) ?? $auth->getRole($item_name);
					if ($item !== null && $auth->remove($item)) {
						echo "- '$item_name' borrada" . ($enUso ? ' (forzada, estaba en uso).' : ' (no usada).') . "\n";
					}
				}
			} else {
				echo "Unused items:" . join(', ', array_keys($all_items)) . "\n";
			}
		}
	}

	/**
	 * Si $itemName (rol o permiso) está asignado a alguna usuaria, directa o
	 * indirectamente a través de los roles que lo tienen como hijo. Antes de
	 * borrar un item "no usado" hay que comprobar esto: createdAt=0 y no
	 * regenerado solo dice que el generador automático ya no lo reconoce,
	 * no que nadie lo tenga concedido todavía.
	 */
	protected function isItemInUse(string $itemName): bool
	{
		$auth = $this->authManager;
		$db = $auth->db;
		$visitados = [];
		$pendientes = [$itemName];
		while ($pendientes) {
			$actual = array_pop($pendientes);
			if (isset($visitados[$actual])) {
				continue;
			}
			$visitados[$actual] = true;
			$asignada = $db->createCommand(
				'SELECT 1 FROM ' . $auth->assignmentTable . ' WHERE item_name = :n LIMIT 1',
				[':n' => $actual]
			)->queryScalar();
			if ($asignada !== false) {
				return true;
			}
			$padres = $db->createCommand(
				'SELECT parent FROM ' . $auth->itemChildTable . ' WHERE child = :n',
				[':n' => $actual]
			)->queryColumn();
			foreach ($padres as $padre) {
				if (!isset($visitados[$padre])) {
					$pendientes[] = $padre;
				}
			}
		}
		return false;
	}

	/**
	 * Lists all permissions, optionally by type
	 */
	public function actionListAll(?string $type = null)
	{
		$no_model_perms = [];
		$prev_model = null;
		if ($type === null || $type === 'perm') {
			$perms = $this->authManager->getItems(Item::TYPE_PERMISSION);
			asort($perms);
			$this->stdout("= PERMISSIONS\n");
			foreach ($perms as $perm) {
				$name = $perm->name;
				if (preg_match( '/([A-Za-z_][A-Za-z_0-9]*).(index|create|view|update|delete|update|report|duplicate|search)/', $name, $m )) {
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
		if ($type === null || $type === 'rol') {
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

		if ($type === null || $type === 'user') {
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
		$auth = $this->authManager;
		$roleItem = $auth->getRole($role);
		if (!$roleItem) {
			$this->stderr("Role '$role' not found.\n");
			return 1;
		}

		// Get direct child roles at the beginning
		$directRoleNames = [];
		foreach ($auth->getChildren($roleItem->name) as $child) {
			if ($child instanceof Role) {
				$directRoleNames[] = $child->name;
			}
		}

		if ($this->format === 'simple') {
			if (!empty($directRoleNames)) {
				$this->stdout("- ".$role.":roles:" . implode(', ', $directRoleNames) . "\n");
			}
			$role_perms = $auth->getPermissionsByRole($role);
			if (count($role_perms)) {
				$this->stdout("- ".$role.":perms:");
				foreach($role_perms as $perm) {
					$this->stdout($perm->name . ", ");
				}
				$this->stdout("\n");
			} else if (empty($directRoleNames)) {
				$this->stdout("- ". $role. "\n");
			}
		} else if ($this->format === 'details') {
			$this->stdout("Role: {$roleItem->name}\n", Console::FG_YELLOW);
			$this->stdout("Description: {$roleItem->description}\n\n");

			$userIds = $auth->getUserIdsByRole($role);
			if ($userIds) {
				$this->stdout("Users (count: " . count($userIds) . "): " . implode(', ', $userIds) . "\n");
			}

			$this->stdout("\nDirect child roles:\n", Console::FG_CYAN);
			if (!empty($directRoleNames)) {
				foreach ($directRoleNames as $childRoleName) {
					$this->stdout("  └─ {$childRoleName}\n", Console::FG_YELLOW);
				}
			} else {
				$this->stdout("  (none)\n");
			}

			$visited = [];
			$printRoleTree = function ($parentName, $depth) use ($auth, &$printRoleTree, &$visited) {
				if (in_array($parentName, $visited, true)) {
					return;
				}
				$visited[] = $parentName;
				$children = $auth->getChildren($parentName);
				$childRoles = [];
				foreach ($children as $name => $item) {
					if ($item instanceof \yii\rbac\Role) {
						$childRoles[] = $name;
					}
				}
				sort($childRoles);
				foreach ($childRoles as $childName) {
					$this->stdout(str_repeat('  ', $depth) . "  └─ {$childName}\n", Console::FG_YELLOW);
					$printRoleTree($childName, $depth + 1);
				}
			};
			$this->stdout("\nAll descendant roles:\n", Console::FG_CYAN);
			foreach ($directRoleNames as $directChildName) {
				$this->stdout("  └─ {$directChildName}\n", Console::FG_YELLOW);
				$printRoleTree($directChildName, 2);
			}

			$this->stdout("\nPermissions:\n", Console::FG_GREEN);
			$permissions = $auth->getPermissionsByRole($role, true, '', false);
			if (empty($permissions)) {
				$this->stdout("  (none)\n");
			} else {
				uksort($permissions, 'strcasecmp');
				foreach ($permissions as $name => $permission) {
					$this->stdout("  └─ {$name}\n", Console::FG_GREEN);
				}
			}
			return 0;
		} else {
			$this->stderr("Unknown format '{$this->format}'. Use 'simple' or 'details'.\n");
			return 1;
		}
	}


	public function actionAssignPermToUser($perm_name, $user_id)
	{
		$permission = $this->authManager->getItem($perm_name);
		if ($permission === null) {
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
			AuthHelper::flushMessages($this->verbose);
		}
	}

	public function actionCreatePermission($perm_name, $perm_desc)
	{
		$permission = AuthHelper::createOrUpdatePermission(
			$perm_name, $perm_desc, false, $this->authManager);
		AuthHelper::flushMessages($this->verbose);
	}

	public function actionCreateRole($perm_name, $perm_desc)
	{
		$permission = AuthHelper::createOrUpdateRole(
			$perm_name, $perm_desc, false, $this->authManager);
		AuthHelper::flushMessages($this->verbose);
	}

	public function actionRemovePermFromRole($perm_name, $role_name)
	{
		AuthHelper::removeFromRole($role_name, $perm_name, $this->authManager);
		AuthHelper::flushMessages($this->verbose);
	}

	public function actionRemoveRoles(array|string $role_names): void
	{
		if (is_string($role_names)) {
			$role_names = array_values(array_filter(array_map('trim', explode(',', $role_names))));
		}
		AuthHelper::removeRoles($role_names, $this->authManager);
		AuthHelper::flushMessages($this->verbose);
	}

	public function actionRemovePermissions(array|string $perm_names): void
	{
		if (is_string($perm_names)) {
			$perm_names = array_values(array_filter(array_map('trim', explode(',', $perm_names))));
		}
		AuthHelper::removePerms($perm_names, $this->authManager);
		AuthHelper::flushMessages($this->verbose);
	}

	public function actionRemoveAllUnused(string $module_id)
	{
		foreach ($this->authManager->getRoles() as $role) {
			if (StringHelper::startsWith("$module_id.", $role->name) && $role->createdAt === 0) {
				$this->authManager->remove($role);
			}
		}
		foreach ($this->authManager->getPermissions() as $perm) {
			if (StringHelper::startsWith("$module_id.", $role->name) && $perm->createdAt === 0) {
				$this->authManager->remove($perm);
			}
		}
	}

	public function actionRemoveAll()
	{
		$this->authManager->removeAll();
		AuthHelper::flushMessages($this->verbose);
	}

	/**
	 * Borra los roles y permisos cuyo nombre coincide con una expresión LIKE
	 * de SQL (p.ej. 'participantes.Participante.importar%'). Los que sigue
	 * teniendo alguna usuaria asignada (directa o por un rol intermedio) no
	 * se tocan, igual que en createModuleRbacPermissions().
	 */
	public function actionRemoveItemsLike(string $like): void
	{
		$auth = $this->authManager;
		$nombres = $auth->db->createCommand(
			'SELECT name FROM ' . $auth->itemTable . ' WHERE name LIKE :like ORDER BY name',
			[':like' => $like]
		)->queryColumn();
		if (!$nombres) {
			echo "No hay roles ni permisos que coincidan con '$like'.\n";
			return;
		}
		foreach ($nombres as $nombre) {
			$enUso = $this->isItemInUse($nombre);
			if ($enUso && empty($this->force)) {
				echo "! No se borra '$nombre': sigue asignada a alguna usuaria (directa o por un rol intermedio).\n";
				continue;
			}
			$item = $auth->getPermission($nombre) ?? $auth->getRole($nombre);
			if ($item !== null && $auth->remove($item)) {
				echo "- '$nombre' borrado" . ($enUso ? ' (forzado, estaba en uso).' : '.') . "\n";
			}
		}
		AuthHelper::flushMessages($this->verbose);
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

	public function actionListAllUnused(string $module_id)
	{
		echo "# Roles\n";
		foreach ($this->authManager->getRoles() as $role) {
			if ($role->createdAt === 0 && preg_match("/$module_id\.[A-Z]([A-Za-z_])*\./", $role->name)) {
				echo $role->name . ' (' . $role->description . ")\n";
			}
		}
		echo "# Permissions\n";
		foreach ($this->authManager->getPermissions() as $perm) {
			if ($perm->createdAt === 0 && preg_match("/$module_id\.[A-Z]([A-Za-z_])*\./", $perm->name)) {
				echo $perm->name . ' (' . $perm->description . ")\n";
			}
		}
	}

	/**
	 * Lists all permissions (direct + inherited) for a given role
	 * @param string $role the role name
	 * @deprecated Use actionListRole with --format=details instead
	 */
	public function actionListRol($role)
	{
		$this->format = 'details';
		return $this->actionListRole($role);
	}


	/**
	 * Lists all roles assigned to a user (direct assignments only)
	 * Accepts user ID, username, or email
	 * @param string|integer $identifier user ID, username or email
	 */
	public function actionListUserRoles($identifier)
	{
		$auth = $this->authManager;

		// Use UserQuery to find user by ID, username, or email
		$userClass = Yii::$app->user->identityClass;
		$userQuery = $userClass::find();
		$user = $userQuery->whereIdOrUsernameOrEmail($identifier)->one();

		if (!$user) {
			$this->stderr("User '$identifier' not found\n");
			return 1;
		}

		$userId = $user->id;

		// Get DIRECT role assignments
		$directAssignments = $auth->getAssignments($userId);

		// Get DEFAULT roles from authManager config (always strings)
		$defaultRoles = $auth->defaultRoles;

		// Combine: direct assignments + ALL default roles (they auto-apply)
		$allRoles = array_unique(array_merge(
			array_keys($directAssignments),
			$defaultRoles  // defaultRoles are ALWAYS strings, no filtering needed
		));

		sort($allRoles);

		if (empty($allRoles)) {
			$this->stdout("User '{$user->username}' [ID: {$userId}]: no roles assigned\n");
			return 0;
		}

		$this->stdout("User: {$user->username}", Console::FG_YELLOW);
		if (isset($user->email)) {
			$this->stdout(" ({$user->email})");
		}
		$this->stdout(" [ID: {$userId}]\n");

		$this->stdout("All roles (direct + default):\n", Console::FG_CYAN);

		foreach ($allRoles as $roleName) {
			$isDirect = isset($directAssignments[$roleName]);
			$role = $auth->getRole($roleName);
			$desc = $role ? $role->description : 'N/A';

			$this->stdout("  └─ {$roleName}", Console::FG_YELLOW);
			if ($this->verbose && $desc !== 'N/A') {
				$this->stdout(" ({$desc})");
			}
			if (!$isDirect && in_array($roleName, $defaultRoles)) {
				$this->stdout(" [DEFAULT]", Console::FG_BLUE);
			}
			$this->stdout("\n");
		}

		return 0;
	}

	/**
	 * Lists all users with their roles and permissions in a tree format
	 * @param int|null $user_id Optional user ID to filter by a specific user
	 */
	public function actionListAllUserPerms(int|string|null $user_id = null, bool $show_perms = false)
	{
		$auth = $this->authManager;
		$userClass = Yii::$app->user->identityClass;
		$userQuery = $userClass::find();
		if ($user_id !== null) {
			if (is_numeric($user_id)) {
				$userQuery->andWhere(['id' => $user_id]);
			} else {
				$userQuery->andWhere(['username' => $user_id]);
			}
		}
		$users = $userQuery->all();

		foreach ($users as $user) {
			$this->stdout("User: {$user->username}", Console::FG_YELLOW);
			if (isset($user->email)) {
				$this->stdout(" ({$user->email})", Console::FG_CYAN);
			}
			$this->stdout(" [ID: {$user->id}]\n");

			$directAssignments = $auth->getAssignments($user->id);
			$defaultRoles = $auth->defaultRoles;
			$allRoles = array_unique(array_merge(
				array_keys($directAssignments),
				$defaultRoles
			));

			if (empty($allRoles)) {
				$this->stdout("  (no roles)\n", Console::FG_GREY);
				continue;
			}

			$processedRoles = [];
			$this->displayRolesTree($allRoles, '  ', $auth, $processedRoles, $show_perms);

			$this->stdout("\n");
		}
	}

	protected function displayRolesTree(array $roleNames, string $indent, $auth, array &$processedRoles, bool $show_perms): void
	{
		// Árbol jerárquico real: cada rol se muestra anidado bajo cada uno de
		// sus padres (hijos directos), aunque salga en varias ramas. Por eso
		// $processedRoles ya no es "vistos globales" sino la rama actual,
		// solo para cortar ciclos.
		$roleNames = array_values(array_unique($roleNames));
		sort($roleNames, SORT_NATURAL | SORT_FLAG_CASE);
		foreach ($roleNames as $roleName) {
			if (in_array($roleName, $processedRoles, true)) {
				continue; // ciclo en esta rama
			}
			$role = $auth->getRole($roleName);
			if (!$role) {
				continue;
			}

			$this->stdout("{$indent}└─ Role: {$role->name}", Console::FG_YELLOW);
			if ($this->verbose && $role->description) {
				$this->stdout(" ({$role->description})", Console::FG_GREY);
			}
			$this->stdout("\n");

			$processedRoles[] = $roleName;
			$childRoleNames = [];
			foreach ($auth->getChildren($roleName) as $childName => $child) {
				if ($child instanceof Role && $childName !== $roleName) {
					$childRoleNames[] = $childName;
				}
			}
			if (!empty($childRoleNames)) {
				$this->displayRolesTree($childRoleNames, $indent . '  ', $auth, $processedRoles, $show_perms);
			}
			array_pop($processedRoles);
			if ($show_perms) {
				$permissions = $auth->getPermissionsByRole($roleName);
				foreach ($permissions as $perm) {
					$this->stdout("{$indent}  └─ {$perm->name}", Console::FG_GREEN);
					if ($this->verbose && $perm->description) {
						$this->stdout(" ({$perm->description})", Console::FG_GREY);
					}
					$this->stdout("\n");
				}
			}
		}
	}

} // class

