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
					   'Gest' => 'Gestionar ',
					   'Dele' => 'Borrar',
					   'Repo' => 'Informes de' ] as $perm_name => $perm_desc) {
				try {
					$permission = $auth->getItem(ucfirst($model::getModelInfo('controller_name')) . "_$perm_name");
					if( !$permission ) {
						$permission = $auth->createPermission(ucfirst($model::getModelInfo('controller_name')) . "_$perm_name" );
						$permission->description = $perm_desc . $model->t('app', " {title_plural}");
						$auth->add($permission);
					}
					if( $perm_name == 'View' || $perm_name == 'Repo' ) {
						$auth->addChild($visora, $permission);
					} else {
						$auth->addChild($editora, $permission);
					}
					if( $perm_name == "Gest" ) {
						$auth->addChild($editora, $permission);
					}
					$auth->addChild($todo, $permission);
					echo $permission->name . ' => ' . $permission->description . ": permiso creado\n";
					if( $perm_name == 'Crea' ) {
						continue;
					}
					$permission = $auth->getItem(ucfirst($model::getModelInfo('controller_name')) . "_{$perm_name}_Propio");
					if (!$permission ) {
						$permission = $auth->createPermission(ucfirst($model::getModelInfo('controller_name')) . "_{$perm_name}_Propio" );
						$permission->description = $perm_desc . $model->t('app', " {title_plural} que me pertenecen");
						$auth->add($permission);
						echo $permission->name . ' => ' . $permission->description . ": permiso creado\n";
					}
				} catch( \yii\db\IntegrityException $e ) {
					echo "Error: permission $model_name::$perm_name already exists\n";
				}
			}
		}
	}

}
