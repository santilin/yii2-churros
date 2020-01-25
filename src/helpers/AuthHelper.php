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

	static public function createModelPermissions($model_names)
	{
		$auth = \Yii::$app->authManager;
// 		$auth->removeAll();

		$todo = $auth->getItem('VerEditarTodo');
		if( !$todo ) {
			$todo = $auth->createRole('VerEditarTodo');
			$todo->description = "Ver y editar todos los ficheros";
			$auth->add($todo);
		}
		$visora = $auth->getItem('VerTodo');
		if (!$visora) {
			$visora = $auth->createRole('VerTodo');
			$visora->description = "Ver todos los ficheros";
			$auth->add($visora);
		}
		$editora = $auth->getItem('EditarTodo');
		if( !$editora ) {
			$editora = $auth->createRole('EditarTodo');
			$editora->description = "Editar todos los ficheros";
			$auth->add($editora);
		}

		foreach( $model_names as $model_name ) {
			$model = new $model_name;
			foreach( [ 'Crea' => 'Crear',
					   'View' => 'Ver',
					   'Edit' => 'Editar',
					   'Dele' => 'Borrar',
					   'Repo' => 'Informes de' ] as $perm_name => $perm_desc) {
				$permission = $auth->getItem(ucfirst($model::getModelInfo('controller_name')) . "_$perm_name");
				if( !$permission ) {
					$permission = $auth->createPermission(ucfirst($model::getModelInfo('controller_name')) . "_$perm_name" );
					$permission->description = $perm_desc . $model->t('app', " {title_plural}");
					$auth->add($permission);
				}
				$add_to_visora = ($perm_name == 'View' || $perm_name == 'Repo');
				$add_to_editora = $add_to_visora || ($perm_name == 'Edit' || $perm_name == 'Crea' || $perm_name == "Dele");
				if( $add_to_visora ) {
					if( !$auth->hasChild($visora, $permission) ) {
						$auth->addChild($visora, $permission);
					} else {
						echo "Error: permission {$permission->description} already exists in role {$visora->description}\n";
					}
				}
				if( $add_to_editora ) {
					if( !$auth->hasChild($editora, $permission) ) {
						$auth->addChild($editora, $permission);
					} else {
						echo "Error: permission {$permission->description} already exists in role {$editora->description}\n";
					}
				}
				if( !$auth->hasChild($todo, $permission) ) {
					$auth->addChild($todo, $permission);
					echo $permission->name . ' => ' . $permission->description . ": permiso creado\n";
				}
				if( $perm_name == 'Crea' ) {
					continue;
				}
				$permission_own = $auth->getItem(ucfirst($model::getModelInfo('controller_name')) . "_{$perm_name}_Own");
				if (!$permission_own ) {
					$permission_own = $auth->createPermission(ucfirst($model::getModelInfo('controller_name')) . "_{$perm_name}_Own" );
					$permission_own->description = $perm_desc . $model->t('churros', " {title_plural} propi{-as}");
					$auth->add($permission_own);
					echo $permission_own->name . ' => ' . $permission_own->description . ": permiso creado\n";
				}
			}
		}
	}

}
