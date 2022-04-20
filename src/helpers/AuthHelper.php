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
	static public function createModelPermissions($model_name, $auth = null)
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
		
		$model = new $model_name;
		$simple_model_name = AppHelper::lastWord($model_name, '\\');
		$model_title = $model->t('app', " {title_plural}");
		$model_editora = $auth->getItem($simple_model_name . '.editor');
		if (!$model_editora) {
			$model_editora = $auth->createRole($simple_model_name . '.editor');
			$model_editora->description = "Editor de $model_title";
			$auth->add($model_editora);
		}
		$model_visora = $auth->getItem($simple_model_name . '.visor');
		if (!$model_visora) {
			$model_visora = $auth->createRole($simple_model_name . '.visor');
			$model_visora->description = "Visor de $model_title";
			$auth->add($model_visora);
		}

		foreach( [ 'create' => 'Crear',
					'view' => 'Ver',
					'edit' => 'Editar',
					'delete' => 'Borrar',
					'report' => 'Informes de' ] as $perm_name => $perm_desc) {
			$permission = $auth->getItem($simple_model_name . ".$perm_name");
			if( !$permission ) {
				$permission = $auth->createPermission($simple_model_name . ".$perm_name" );
				$permission->description = $perm_desc . $model_title;
				$auth->add($permission);
			}
			$add_to_visora = ($perm_name == 'view' || $perm_name == 'report');
			$add_to_editora = $add_to_visora || ($perm_name == 'edit' || $perm_name == 'create' || $perm_name == "delete");
			if( $add_to_visora ) {
				if( !$auth->hasChild($visora, $permission) ) {
					$auth->addChild($visora, $permission);
				} else {
					echo "Error: permission {$permission->name} already exists in role {$visora->name}\n";
				}
				if( !$auth->hasChild($model_visora, $permission) ) {
					$auth->addChild($model_visora, $permission);
				} else {
					echo "Error: permission {$permission->name} already exists in role {$model_visora->name}\n";
				}
			}
			if( !$auth->hasChild($model_editora, $permission) ) {
				$auth->addChild($model_editora, $permission);
			} else {
				echo "Error: permission {$permission->name} already exists in role {$model_editora->name}\n";
			}
			if( !$auth->hasChild($editora, $permission) ) {
				$auth->addChild($editora, $permission);
			} else {
				echo "Error: permission {$permission->name} already exists in role {$editora->name}\n";
			}
			echo $permission->name . ' => ' . $permission->description . ": permiso creado\n";
			if( $perm_name == 'create' ) {
				continue;
			}
			$permission_own = $auth->getItem($simple_model_name . ".{$perm_name}.own");
			if (!$permission_own ) {
				$permission_own = $auth->createPermission($simple_model_name . ".{$perm_name}.own" );
				$permission_own->description = $perm_desc . $model->t('churros', " {title_plural} propi{-as}");
				$auth->add($permission_own);
				echo $permission_own->name . ' => ' . $permission_own->description . ": permiso creado\n";
			} else {
				echo "Error: permission {$permission_own->name} already exists in role {$editora->name}\n";
			}
		}
	}

}
