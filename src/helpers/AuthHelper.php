<?php
/**
 * @link
 * @copyright
 * @license
 * @todo prunePermissions(), removeModelPermissions($model_names, $force)
 */

namespace santilin\churros\helpers;

use Yii;

class AuthHelper
{
	static public function createModelPermissions($model_name, $model_class, $auth = null)
	{
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}

		$visora = $auth->getItem('Visor');
		if (!$visora) {
			$visora = $auth->createRole('Visor');
			$visora->description = "Ver todos los ficheros";
			$auth->add($visora);
		}
		$editora = $auth->getItem('Editor');
		if( !$editora ) {
			$editora = $auth->createRole('Editor');
			$editora->description = "Editar todos los ficheros";
			$auth->add($editora);
		}

		$model = new $model_class;
		$model_title = $model->t('app', " {title_plural}");
		$model_editora = $auth->getItem($model_name . '.editor');
		if (!$model_editora) {
			$model_editora = $auth->createRole($model_name . '.editor');
			$model_editora->description = "Editor de $model_title";
			$auth->add($model_editora);
		}
		$model_visora = $auth->getItem($model_name . '.visor');
		if (!$model_visora) {
			$model_visora = $auth->createRole($model_name . '.visor');
			$model_visora->description = "Visor de $model_title";
			$auth->add($model_visora);
		}
		$model_editora_own = $auth->getItem($model_name . '.editor.own');
		if (!$model_editora_own) {
			$model_editora_own = $auth->createRole($model_name . '.editor.own');
			$model_editora_own->description = "Editor de sus $model_title";
			$auth->add($model_editora_own);
		}
		$model_visora_own = $auth->getItem($model_name . '.visor.own');
		if (!$model_visora_own) {
			$model_visora_own = $auth->createRole($model_name . '.visor.own');
			$model_visora_own->description = "Visor de sus $model_title";
			$auth->add($model_visora_own);
		}

		foreach( [ 'create' => 'Crear',
					'view' => 'Ver',
					'edit' => 'Editar',
					'delete' => 'Borrar',
					'report' => 'Informes de' ] as $perm_name => $perm_desc) {
			$permission = $auth->getItem($model_name . ".$perm_name");
			if( !$permission ) {
				$permission = $auth->createPermission($model_name . ".$perm_name" );
				$permission->description = $perm_desc . $model_title;
				$auth->add($permission);
			}
			$add_to_visora = ($perm_name == 'view' || $perm_name == 'report');
			$add_to_editora = $add_to_visora || ($perm_name == 'edit' || $perm_name == 'create' || $perm_name == "delete");
			if( $add_to_visora ) {
				if( !$auth->hasChild($visora, $permission) ) {
					$auth->addChild($visora, $permission);
				} else {
					echo "Warning: permission {$permission->name} already exists in role {$visora->name}\n";
				}
				if( !$auth->hasChild($model_visora, $permission) ) {
					$auth->addChild($model_visora, $permission);
				} else {
					echo "Warning: permission {$permission->name} already exists in role {$model_visora->name}\n";
				}
			}
			if( !$auth->hasChild($model_editora, $permission) ) {
				$auth->addChild($model_editora, $permission);
			} else {
				echo "Warning: permission {$permission->name} already exists in role {$model_editora->name}\n";
			}
			if( !$auth->hasChild($editora, $permission) ) {
				$auth->addChild($editora, $permission);
			} else {
				echo "Warning: permission {$permission->name} already exists in role {$editora->name}\n";
			}
			echo $permission->name . ' => ' . $permission->description . ": permiso creado\n";
			if( $perm_name != 'create' ) {
				$permission_own = $auth->getItem($model_name . ".$perm_name.own");
				if (!$permission_own ) {
					$permission_own = $auth->createPermission($model_name . ".$perm_name.own" );
					$permission_own->description = $perm_desc . $model->t('churros', " sus {title_plural}");
					$auth->add($permission_own);
					echo $permission_own->name . ' => ' . $permission_own->description . ": permiso creado\n";
				} else {
					echo "Warning: permission {$permission_own->name} already exists in role {$editora->name}\n";
				}
			}
			if( $add_to_editora ) {
				if( $perm_name == 'create' ) {
					$permission_own = $permission;
				}
				if( !$auth->hasChild($model_editora_own, $permission_own) ) {
					$auth->addChild($model_editora_own, $permission_own);
				} else {
					echo "Warning: permission {$permission->name} already exists in role {$model_editora_own->name}\n";
				}
			}
			if( $add_to_visora ) {
				if( !$auth->hasChild($model_visora_own, $permission_own) ) {
					$auth->addChild($model_visora_own, $permission_own);
				} else {
					echo "Warning: permission {$permission->name} already exists in role {$model_editora_own->name}\n";
				}
			}
		}
	}

	static public function createModuleModelPermissions($module_name, $model_name, $model_class, $auth = null)
	{
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}

		$model = new $model_class;
		$model_title = $model->t('app', " {title_plural}");

		$role_name = "module.$module_name.menu.all";
		$role_all = $auth->getItem($role_name);
		if( !$role_all ) {
			$role_all = $auth->createRole($role_name);
			$role_all->description = "Acceso a todos los modelos del módulo $module_name";
			$auth->add($role_all);
			echo $role_all->name . ' => ' . $role_all->description . ": permiso creado\n";
		}

		$perm_name = "module.$module_name.menu.$model_name";
		$permission = $auth->getItem($perm_name);
		if( !$permission ) {
			$permission = $auth->createPermission($perm_name);
			$permission->description = "Acceso al menú de $model_title del módulo $module_name";
			$auth->add($permission);
			echo $permission->name . ' => ' . $permission->description . ": permiso creado\n";
		}
		if( !$auth->hasChild($role_all, $permission) ) {
			$auth->addChild($role_all, $permission);
		} else {
			echo "Warning: permission {$permission->name} already exists in role {$role_all->name}\n";
		}
	}

	public function createPermission($perm_name, $perm_desc, $auth = null)
	{
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		$permission = $auth->getItem($perm_name);
		if( !$permission ) {
			$permission = $auth->createPermission($perm_name);
			$permission->description = $perm_desc;
			$auth->add($permission);
			echo $permission->name . ' => ' . $permission->description . ": permiso creado\n";
		} else {
			if( $permission->description != $perm_desc ) {
				$permission->description = $perm_desc;
				$permission->save();
			}
			echo $permission->name . ' => ' . $permission->description . ": permiso actualizado\n";
		}
	}
	
}
