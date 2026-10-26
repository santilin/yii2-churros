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
	 * @var bool Forces the deletion of roles/permissions even if some user has
	 * them assigned (--force=1). Without force, items in use are kept.
	 */
	public bool $force = false;
	/** @var string Output format for list-role-tree: 'simple' or 'details' */
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
	 *
	 * @param array $owner_roles module roles of the '-owner' chain created
	 *   by createModuleRbacPermissions(): key 'viewer'|'editor'|
	 *   'deleter' => Role|null. With an empty array (apps that do not forward
	 *   it yet) the model '-owner' chain and its
	 *   view-owner/update-owner/delete-owner permissions are not created.
	 */
	public function createControllerPermissions(string $module_id, string $module_desc,
		string $model_name, array $controller,
		?Role $viewer, ?Role $creator, ?Role $editor, ?Role $full_editor,
		?Role $deleter, ?Role $granter, ?Role $admin,
		array $owner_roles = [], array &$all_items = [])
	{
		$model_class = $controller['class'];
		if (!class_exists($model_class)) {
			return;
		}
		$auth = $this->authManager;
		$model = $model_class::instance();
		$model_title = $model->t('app', "{Title_plural}");

		// access_filters is the controller's own or, if it has none, the one inherited
		// from the module (see ModuleControllerDefinition::accessFilters() in capel) —
		// same criteria as createWebControllerPermissions(). If the key does not
		// exist yet (capel not regenerated with the step that emits it for
		// `controllers`), the default 'rbac' keeps the current behavior.
		$access_filters = $controller['access_filters'] ?? ['rbac'];
		// access_filters can combine several values (e.g. ['module', 'rbac']):
		// when there is more than one, the permissions of the one that creates
		// the most are built, from least to most: admin (creates nothing, access
		// does not go through permissions) < module (a single all-or-nothing
		// permission) < rbac (the full viewer/creator/.../admin set per model
		// plus those of `perms`). Checking 'module' before 'rbac' meant that,
		// with both present, neither per-model roles nor `perms` permissions were ever created.
		if (!in_array('rbac', $access_filters, true)) {
			if (in_array('module', $access_filters, true)) {
				$permission = AuthHelper::createOrUpdatePermission(
					"$module_id.$model_name",
					Yii::t('churros', '{module}: {model}: full access', [
						'module' => $module_desc, 'model' => $model_title,
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
			}
			// without 'rbac' or 'module', access does not go through permissions
			// (admin/logged/username/...) and there is nothing else to create.
			return;
		}

		$model_viewer = $model_creator = $model_editor = $model_full_editor
			= $model_deleter = $model_granter = $model_admin = null;
		// Create model roles
		if ($viewer) {
			$model_viewer = AuthHelper::createOrUpdateRole(
				str_replace('.', ".{$model_name}.", $viewer->name),
				Yii::t('churros', '{module}: {model}: viewer', [
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
				Yii::t('churros', '{module}: {model}: creator', [
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
				Yii::t('churros', '{module}: {model}: deleter', [
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
				Yii::t('churros', '{module}: {model}: editor', [
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
				Yii::t('churros', '{module}: {model}: granter', [
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
				Yii::t('churros', '{module}: {model}: full editor', [
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
				Yii::t('churros', '{module}: {model}: administrator', [
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

		// '-owner' chain, totally independent from the base one: no hierarchy
		// among the -owner (editor-owner does NOT contain viewer-owner) and no
		// link at all to the normal viewer/editor/... roles. It is only created
		// to grant rights over one's own records. Each one hangs from its
		// homologous '-owner' module role.
		$model_perm_name = $module_id . '.' . $model_name;
		$owner_descs = [
			'viewer' => 'viewer',
			'editor' => 'editor',
			'deleter' => 'deleter',
		];
		foreach ($owner_descs as $owner_key => $owner_desc) {
			$owner = $owner_roles[$owner_key] ?? null;
			if ($owner === null) {
				continue;
			}
			$model_owner = AuthHelper::createOrUpdateRole(
				str_replace('.', ".{$model_name}.", $owner->name),
				Yii::t('churros', '{module}: {model}: {desc} (own records only)', [
					'module' => $module_desc, 'model' => $model_title,
					'desc' => $owner_desc,
				]), true, $auth);
			unset($all_items[$model_owner->name]);
			AuthHelper::flushMessages($this->verbose);
			if (!$auth->hasChild($owner, $model_owner)) {
				$auth->addChild($owner, $model_owner);
				echo "+ Role '{$model_owner->name}' added to role '{$owner->name}'\n";
			} elseif ($this->verbose) {
				echo "= Role '{$model_owner->name}' already exists in role {$owner->name}\n";
			}
		}
		// Controller '-owner' permissions: view-owner/index-owner/
		// update-owner/delete-owner.
		// They are only created for the actions that the controller declares in
		// `perms`, and hang from the corresponding '-owner' MODULE role
		// (viewer-owner/editor-owner/deleter-owner): neither from the model role
		// nor from any role of the base chain. Each one also contains its
		// same-named base permission (view-owner contains view, and so on), so
		// that a user with ...view-owner also passes a plain can('...view')
		// check; see the note where the base permissions are distributed.
		// `index-owner` is the one used by createSearchModel() to list only the
		// user's own records (CrudController's CREATE_SEARCH_MODEL.OWNERACCESS).
		$declared_perms = [];
		foreach ($controller['perms'] as $perm_name) {
			$perm_name = mb_lcfirst($perm_name);
			if (!str_contains($perm_name, '.')) {
				$declared_perms[] = $perm_name;
			}
		}
		foreach ([
			'view-owner' => [['view', 'index'], 'viewer'],
			'index-owner' => [['index'], 'viewer'],
			'update-owner' => [['update'], 'editor'],
			'delete-owner' => [['delete'], 'deleter'],
		] as $owner_perm => [$actions, $owner_key]) {
			$owner = $owner_roles[$owner_key] ?? null;
			if ($owner === null || !array_intersect($actions, $declared_perms)) {
				continue;
			}
			$permission = AuthHelper::createOrUpdatePermission(
				$model_perm_name . '.' . $owner_perm,
				Yii::t('churros', '{module}: {model}: {perm} (own records only)', [
					'module' => $module_desc, 'model' => $model_title,
					'perm' => $owner_perm,
				]), true, $auth);
			unset($all_items[$permission->name]);
			AuthHelper::flushMessages($this->verbose);
			if (!$auth->hasChild($owner, $permission)) {
				$auth->addChild($owner, $permission);
				echo "+ Permission '{$permission->name}' added to role '{$owner->name}'\n";
			} elseif ($this->verbose) {
				echo "= Permission '{$permission->name}' already exists in role {$owner->name}\n";
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

		foreach ($controller['perms'] as $perm_name) {
			$perm_name = mb_lcfirst($perm_name);
			// An already qualified value (with a dot) is a direct reference to a
			// minimum permission that is meant to be declared as is, without the
			// usual "<module>.<Model>." prefix (e.g. to ask for the module's own
			// admin role, as in ceutae/AcogidaAlojamiento: perms: ['ceutae.admin']).
			// If it matches a ROLE that already exists, that action is already
			// covered by it -there is nothing to create-: a permission with the
			// same name would clash, `auth_item.name` is unique among roles and permissions.
			if (str_contains($perm_name, '.')) {
				if ($auth->getRole($perm_name) !== null) {
					continue;
				}
				$full_perm_name = $perm_name;
			} else {
				$full_perm_name = $model_perm_name . "." . $perm_name;
			}
			$perm_desc = Yii::t('churros', '{module}: {model}: {perm}', [
				'module' => $module_desc,
				'model' => $model_title,
				'perm' => Yii::t('churros', $perm_name)]);
			$permission = AuthHelper::createOrUpdatePermission(
				$full_perm_name, $perm_desc, true, $auth);
			unset($all_items[$permission->name]);
			AuthHelper::flushMessages($this->verbose);
			// Every '-owner' permission includes its same-named base one
			// (view-owner contains view, and so on), so that whoever has
			// ...view-owner also passes a plain `can('...view')` check; the
			// "own records only" restriction is enforced by the rule that uses
			// the -owner permission, not by RBAC itself.
			$owner_counterpart = [
				'view' => 'view-owner', 'index' => 'index-owner',
				'update' => 'update-owner', 'delete' => 'delete-owner',
			][$perm_name] ?? null;
			if ($owner_counterpart !== null && !str_contains($perm_name, '.')) {
				$owner_permission = $auth->getPermission($model_perm_name . '.' . $owner_counterpart);
				if ($owner_permission !== null && !$auth->hasChild($owner_permission, $permission)) {
					$auth->addChild($owner_permission, $permission);
					echo "+ Permission '{$permission->name}' added to permission '{$owner_permission->name}'\n";
				} elseif ($owner_permission !== null && $this->verbose) {
					echo "= Permission '{$permission->name}' already exists in permission {$owner_permission->name}\n";
				}
			}
			// Distributed ONLY along the base chain (the '-owner' chain does not
			// receive the normal permissions: its own ...-owner ones are what it gets).
			$roles_to_add = [];
			switch ($perm_name) {
				case 'view':
				case 'index':
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
	}

	/**
	 * Creates the permissions of `web_controllers` (flat web controllers, no
	 * model): capel generates one permission per action there just like with
	 * the 6 standard ones of `controllers` (same `ControllerActionDefinition::`
	 * permissions()` underneath). Unlike createControllerPermissions(), there
	 * is no viewer/creator/editor hierarchy to build — a web controller has
	 * no permission convention of its own —, so each permission hangs directly
	 * from $admin; if a concrete action needs another role, it is reassigned
	 * by hand (as it was done before this was generated automatically).
	 *
	 * A controller with `access_filters: module` does not distribute per action —
	 * `ModuleRbacAccessRule` in that case checks the whole controller permission
	 * `<module>.<Controller>`, not one per action— so there a single permission
	 * is created instead of one for each entry of `perms`.
	 *
	 * @param array $web_controllers `Capel::MODULES[$module_id]['web_controllers']`:
	 *   controller name -> ['perms' => [action names], 'access_filters' => [...]]
	 */
	public function createWebControllerPermissions(string $module_id, string $module_desc,
		array $web_controllers, ?Role $viewer, ?Role $editor, ?Role $admin, array &$all_items = [])
	{
		$auth = $this->authManager;
		foreach ($web_controllers as $cname => $wc) {
			// access_filters is the controller's own or, if it has none, the one inherited
			// from the module (see ModuleControllerDefinition::accessFilters() in capel).
			$access_filters = $wc['access_filters'] ?? [];
			// Same criteria as createControllerPermissions(): with several values
			// at once (e.g. ['module', 'rbac']), the permissions of the one that
			// creates the most are built, from least to most: admin (nothing) <
			// module (an all-or-nothing permission) < rbac (those of `perms`).
			// Checking 'module' before 'rbac' meant that, with both present,
			// none of those of `perms` were ever created.
			if (!in_array('rbac', $access_filters, true)) {
				if (in_array('module', $access_filters, true)) {
					$permission = AuthHelper::createOrUpdatePermission(
						"$module_id.$cname",
						Yii::t('churros', '{module}: {controller}: full access', [
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
				}
				// without 'rbac' or 'module', access does not go through permissions
				// (admin/logged/username/...) and there is nothing else to create.
				continue;
			}
			foreach ($wc['perms'] ?? [] as $perm_name) {
				// Same criteria as createControllerPermissions(): an already
				// qualified value (with a dot) is a direct reference to a minimum
				// permission, not an action name; if it matches a ROLE that already
				// exists, that action is already covered by it, nothing to create
				// (and a permission with the same name would clash anyway).
				if (str_contains($perm_name, '.')) {
					if ($auth->getRole($perm_name) !== null) {
						continue;
					}
					$full_perm_name = $perm_name;
				} else {
					// <module>.<Controller>.<permission>, just like ModuleRbacAccessRule::
					// matchCrudAction() for CRUD and the already existing Ceuta
					// permissions (participantes.Ceuta.actuaciones, granted by hand so far).
					$full_perm_name = "$module_id.$cname.$perm_name";
				}
				$perm_desc = Yii::t('churros', '{module}: {controller}: {perm}', [
					'module' => $module_desc,
					'controller' => $cname,
					'perm' => $perm_name,
				]);
				// Special case `index`: it is the module's front page (the `Site`
				// controller declares it as such), so besides hanging from $admin it
				// also hangs from $viewer/$editor -it used to be done by a separate
				// block in createControllerPermissions(), redundant with this permission-.
				$permission = AuthHelper::createOrUpdatePermission(
					$full_perm_name, $perm_desc, true, $auth);
				unset($all_items[$permission->name]);
				AuthHelper::flushMessages($this->verbose);
				$roles_to_add = [$admin];
				if ($perm_name === 'index') {
					$roles_to_add[] = $viewer;
					$roles_to_add[] = $editor;
				}
				foreach (array_filter($roles_to_add) as $role_to_add) {
					if (!$auth->hasChild($role_to_add, $permission)) {
						$auth->addChild($role_to_add, $permission);
						echo "+ Permission '{$permission->name}' added to role '{$role_to_add->name}'\n";
					} elseif ($this->verbose) {
						echo "= Permission '{$permission->name}' already exists in role {$role_to_add->name}\n";
					}
				}
			}
		}
	}

	/**
	 * Creates the permissions for a rbac module and shows the ones not used
	 *
	 * @param array $roles_to_create module roles to create. 'owner' adds the
	 * '-owner' chain (viewer/editor/deleter -owner), totally independent from
	 * the base one: no hierarchy among them nor links to the normal roles.
	 * @param bool $deleteUnused if true, instead of only listing the unused
	 * items (createdAt=0 and not regenerated in this pass) it deletes them --
	 * but only those that isItemInUse() says nobody has assigned, neither
	 * directly nor inherited through an intermediate role.
	 */
	public function createModuleRbacPermissions(string $module_id, array $module_info,
		array $roles_to_create, bool $deleteUnused = false)
	{
		$auth = $this->authManager;
		// keeps track of all module rules to keep default ones: createdAt=0
		// marks the items that this very automatic generation created (see
		// AuthHelper::createOrUpdateRole()/createOrUpdatePermission() with
		// $is_default=true); one created by hand (actionIndex(), permisosCeuta()...)
		// has a real timestamp and does not have to be listed here even if this
		// pass does not touch it, because it is never its job to touch it.
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
		AuthHelper::createOrUpdateRole($module_id, $module_desc, true, $this->authManager);

		if (in_array('viewer', $roles_to_create)) {
			$viewer = AuthHelper::createOrUpdateRole("$module_id.viewer",
				Yii::t('churros', '{module}:  viewer ', ['module' => $module_desc]), true, $auth);
			unset($all_items[$viewer->name]);
			AuthHelper::flushMessages($this->verbose);
		} else {
			$viewer = null;
		}
		if (in_array('creator', $roles_to_create)) {
			$creator = AuthHelper::createOrUpdateRole("$module_id.creator",
				Yii::t('churros', '{module}:  creator', ['module' => $module_desc]), true, $auth);
			unset($all_items[$creator->name]);
			AuthHelper::flushMessages($this->verbose);
		} else {
			$creator = null;
		}
		if (in_array('editor', $roles_to_create)) {
			$editor = AuthHelper::createOrUpdateRole("$module_id.editor",
				Yii::t('churros', '{module}:  editor', ['module' => $module_desc]), true, $auth);
			unset($all_items[$editor->name]);
			AuthHelper::flushMessages($this->verbose);
		} else {
			$editor = null;
		}
		if (in_array('full-editor', $roles_to_create)) {
			$full_editor = AuthHelper::createOrUpdateRole("$module_id.full-editor",
				Yii::t('churros', '{module}:  full editor', ['module' => $module_desc]), true, $auth);
			unset($all_items[$full_editor->name]);
			AuthHelper::flushMessages($this->verbose);
		} else {
			$full_editor = null;
		}
		if (in_array('deleter', $roles_to_create)) {
			$deleter = AuthHelper::createOrUpdateRole("$module_id.deleter",
				Yii::t('churros', '{module}:  deleter', ['module' => $module_desc]), true, $auth);
			unset($all_items[$deleter->name]);
			AuthHelper::flushMessages($this->verbose);
		} else {
			$deleter = null;
		}
		if (in_array('granter', $roles_to_create)) {
			$granter = AuthHelper::createOrUpdateRole("$module_id.granter",
				Yii::t('churros', '{module}:  granter', ['module' => $module_desc]), true, $auth);
			unset($all_items[$granter->name]);
			AuthHelper::flushMessages($this->verbose);
		} else {
			$granter = null;
		}
		if (in_array('admin', $roles_to_create)) {
			$admin = AuthHelper::createOrUpdateRole("$module_id.admin",
				Yii::t('churros', '{module}:  administrator', ['module' => $module_desc]), true, $auth);
			unset($all_items[$admin->name]);
			AuthHelper::flushMessages($this->verbose);
		} else {
			$admin = null;
		}

		// '-owner' chain: viewer/editor/deleter -owner, only if 'owner' is
		// in $roles_to_create and its base role is going to be created. Totally
		// INDEPENDENT from the base one: no hierarchy among the -owner nor any
		// link with the normal roles, to grant over one's own records.
		// There is no full-editor-owner.
		$owner_roles = [];
		if (in_array('owner', $roles_to_create)) {
			foreach ([
				'viewer' => [$viewer, 'viewer'],
				'editor' => [$editor, 'editor'],
				'deleter' => [$deleter, 'deleter'],
			] as $owner_key => [$owner_base, $owner_desc]) {
				$owner_roles[$owner_key] = null;
				if ($owner_base === null) {
					continue;
				}
				$owner = AuthHelper::createOrUpdateRole(
					$owner_base->name . '-owner',
					Yii::t('churros', '{module}:  {desc} (own records only)', [
						'module' => $module_desc, 'desc' => $owner_desc,
					]), true, $auth);
				unset($all_items[$owner->name]);
				AuthHelper::flushMessages($this->verbose);
				$owner_roles[$owner_key] = $owner;
			}
		}

		foreach ($module_info['controllers']??[] as $cname => $controller) {
			$this->createControllerPermissions($module_id, $module_desc, $cname, $controller,
				$viewer, $creator, $editor, $full_editor, $deleter, $granter, $admin,
				$owner_roles, $all_items);
			AuthHelper::flushMessages($this->verbose);
		}
		if (!empty($module_info['web_controllers'])) {
			$this->createWebControllerPermissions($module_id, $module_desc,
				$module_info['web_controllers'], $viewer, $editor, $admin, $all_items);
			AuthHelper::flushMessages($this->verbose);
		}

		// list unused (or delete, with --deleteUnused=1)
		if (count($all_items)) {
			if ($deleteUnused) {
				foreach (array_keys($all_items) as $item_name) {
					$enUso = $this->isItemInUse($item_name);
					if ($enUso && empty($this->force)) {
						echo "! '$item_name' not deleted: still assigned to a user (directly or through an intermediate role).\n";
						continue;
					}
					$item = $auth->getPermission($item_name) ?? $auth->getRole($item_name);
					if ($item !== null && $auth->remove($item)) {
						echo "- '$item_name' deleted" . ($enUso ? ' (forced, it was in use).' : ' (unused).') . "\n";
					}
				}
			} else {
				echo "Unused items:" . join(', ', array_keys($all_items)) . "\n";
			}
		}
	}

	/**
	 * Whether $itemName (role or permission) is assigned to some user, directly
	 * or indirectly through the roles that have it as a child. Before deleting
	 * an "unused" item this must be checked: createdAt=0 and not regenerated
	 * only says that the automatic generator no longer recognizes it, not that
	 * nobody still has it granted.
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
	 * Lists all permissions and roles, optionally by type
	 * @param string|null $type 'perm' or 'role'. Null lists both
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
		if ($type === null || $type === 'role') {
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
	 * Lists the tree of a role: its descendants, with the permissions
	 * interleaved where they hang. A role already shown somewhere else in the
	 * tree is not expanded again: '... shown above ...' is printed instead
	 * of repeating its permissions.
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
		} else if ($this->format === 'tree') {
			$this->stdout("Role: {$roleItem->name}\n", Console::FG_YELLOW);
			$this->stdout("Description: {$roleItem->description}\n\n");

			$userIds = $auth->getUserIdsByRole($role);
			if ($userIds) {
				$this->stdout("Users (count: " . count($userIds) . "): " . implode(', ', $userIds) . "\n");
			}

			$visited = [$roleItem->name => true];
			$printTree = function (string $parentName, int $depth) use ($auth, &$printTree, &$visited) {
				$children = $auth->getChildren($parentName);
				uasort($children, fn($a, $b) => strcasecmp($a->name, $b->name));
				$names = array_keys($children);
				$last_index = count($names) - 1;
				$indent = str_repeat('  ', $depth + 1);
				foreach ($names as $index => $childName) {
					$item = $children[$childName];
					$glyph = $index === $last_index ? '└─ ' : '├─ ';
					if ($item instanceof Role) {
						if (isset($visited[$childName])) {
							$this->stdout("{$indent}{$glyph}{$childName}  ... shown above ...\n", Console::FG_YELLOW);
							continue;
						}
						$visited[$childName] = true;
						$this->stdout("{$indent}{$glyph}{$childName}\n", Console::FG_YELLOW);
						$printTree($childName, $depth + 1);
					} else {
						$this->stdout("{$indent}{$glyph}{$childName}\n", Console::FG_GREEN);
					}
				}
			};
			$this->stdout("\nTree:\n", Console::FG_CYAN);
			if (empty($auth->getChildren($roleItem->name))) {
				$this->stdout("  (none)\n");
			} else {
				$printTree($roleItem->name, 0);
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
	 * Deletes the roles and permissions whose name matches a SQL LIKE
	 * expression (e.g. 'participantes.Participante.importar%'). Those that
	 * still have some user assigned (directly or through an intermediate role)
	 * are not touched, just like in createModuleRbacPermissions().
	 */
	public function actionRemoveItemsLike(string $like): void
	{
		$auth = $this->authManager;
		$nombres = $auth->db->createCommand(
			'SELECT name FROM ' . $auth->itemTable . ' WHERE name LIKE :like ORDER BY name',
			[':like' => $like]
		)->queryColumn();
		if (!$nombres) {
			echo "No roles or permissions match '$like'.\n";
			return;
		}
		foreach ($nombres as $nombre) {
			$enUso = $this->isItemInUse($nombre);
			if ($enUso && empty($this->force)) {
				echo "! '$nombre' not deleted: still assigned to a user (directly or through an intermediate role).\n";
				continue;
			}
			$item = $auth->getPermission($nombre) ?? $auth->getRole($nombre);
			if ($item !== null && $auth->remove($item)) {
				echo "- '$nombre' deleted" . ($enUso ? ' (forced, it was in use).' : '.') . "\n";
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
	 * Lists all roles assigned to a user (direct assignments only)
	 * Accepts user ID, username, or email
	 * @param string|integer $identifier user ID, username or email
	 */
	public function actionDirectUserRoles($identifier)
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

		// Get DIRECT assignments only (roles and permissions)
		$directAssignments = $auth->getAssignments($userId);

		$this->stdout("User: {$user->username}", Console::FG_YELLOW);
		if (isset($user->email)) {
			$this->stdout(" ({$user->email})");
		}
		$this->stdout(" [ID: {$userId}]\n");

		$this->stdout("Direct assignments (roles and permissions):\n", Console::FG_CYAN);

		$itemNames = array_keys($directAssignments);
		sort($itemNames, SORT_NATURAL | SORT_FLAG_CASE);
		if (empty($itemNames)) {
			$this->stdout("  (none)\n", Console::FG_GREY);
			return 0;
		}
		foreach ($itemNames as $itemName) {
			if ($auth->getRole($itemName) !== null) {
				$this->stdout("  └─ Role: {$itemName}", Console::FG_YELLOW);
			} else {
				$this->stdout("  └─ {$itemName}", Console::FG_GREEN);
			}
			if ($this->verbose) {
				$item = $auth->getRole($itemName) ?? $auth->getPermission($itemName);
				if ($item !== null && $item->description) {
					$this->stdout(" ({$item->description})", Console::FG_GREY);
				}
			}
			$this->stdout("\n");
		}

		return 0;
	}

	/**
	 * Lists all users with their roles and permissions in a tree format
	 * @param int|null $user_id Optional user ID to filter by a specific user
	 */
	public function actionUserRoles(int|string|null $user_id = null, bool $show_perms = false)
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


	public function actionUserPerms(int|string|null $user_id = null)
	{
		return $this->actionUserRoles($user_id, true);
	}

	/**
	 * Lists the users that have the indicated roles/permissions (separated by
	 * commas), directly or inherited through intermediate roles.
	 * @param string $items comma separated names
	 * @param string $mode all (every one, default) or any (some)
	 */
	public function actionUsersWithPerms(string $items, string $mode = 'all'): void
	{
		$auth = $this->authManager;
		$nombres = array_values(array_filter(array_map('trim', explode(',', $items))));
		if (!$nombres) {
			$this->stderr("Enter at least one role or permission, comma separated.\n");
			return;
		}
		$mode = strtolower($mode) === 'any' ? 'any' : 'all';
		$porItem = [];
		foreach ($nombres as $nombre) {
			$item = $auth->getRole($nombre) ?? $auth->getPermission($nombre);
			if ($item === null) {
				$this->stderr("'$nombre' does not exist as a role or a permission.\n");
				return;
			}
			$porItem[$nombre] = $this->userIdsWithItem($nombre);
		}
		$userIds = $mode === 'any'
			? array_unique(array_merge(...array_values($porItem)))
			: array_intersect(...array_values($porItem));
		$userIds = array_values(array_map('intval', $userIds));
		if (!$userIds) {
			$this->stdout("No user has " . ($mode === 'any' ? 'any' : 'all') . " of: " . implode(', ', $nombres) . ".\n");
			return;
		}
		$userClass = Yii::$app->user->identityClass;
		$usuarios = $userClass::find()->andWhere(['id' => $userIds])->all();
		$this->stdout(count($usuarios) . " user(s) with " . ($mode === 'any' ? 'any' : 'all') . " of: " . implode(', ', $nombres) . ".\n", Console::FG_CYAN);
		foreach ($usuarios as $u) {
			$this->stdout("- {$u->username}", Console::FG_YELLOW);
			if (isset($u->email)) {
				$this->stdout(" ({$u->email})", Console::FG_CYAN);
			}
			$this->stdout(" [ID: {$u->id}]\n");
		}
	}

	/**
	 * Ids of users with an item (role or permission), directly or inherited
	 * through the roles that include it, transitively.
	 * @return int[]
	 */
	protected function userIdsWithItem(string $itemName): array
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
		$ph = [];
		$params = [];
		foreach (array_keys($visitados) as $i => $nombre) {
			$ph[] = ":n$i";
			$params[":n$i"] = $nombre;
		}
		return array_map('intval', $db->createCommand(
			'SELECT DISTINCT user_id FROM ' . $auth->assignmentTable
			. ' WHERE item_name IN (' . implode(',', $ph) . ')',
			$params
		)->queryColumn());
	}

	protected function displayRolesTree(array $roleNames, string $indent, $auth, array &$processedRoles, bool $show_perms, array &$shownPerms = []): void
	{
		// Real hierarchical tree: each role is shown nested under each one of
		// its parents (direct children), even if it appears in several branches.
		// That is why $processedRoles is no longer "globally seen" but the
		// current branch, only to cut cycles. Permissions, on the other hand,
		// are shown only once in the whole tree so as not to repeat them under each role.
		$roleNames = array_values(array_unique($roleNames));
		sort($roleNames, SORT_NATURAL | SORT_FLAG_CASE);
		foreach ($roleNames as $roleName) {
			if (in_array($roleName, $processedRoles, true)) {
				continue; // cycle in this branch
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
				$this->displayRolesTree($childRoleNames, $indent . '  ', $auth, $processedRoles, $show_perms, $shownPerms);
			}
			array_pop($processedRoles);
			if ($show_perms) {
				$permissions = $auth->getPermissionsByRole($roleName);
				foreach ($permissions as $perm) {
					if (isset($shownPerms[$perm->name])) {
						continue;
					}
					$shownPerms[$perm->name] = true;
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

