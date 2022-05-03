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
	static public function createOrUpdatePermission($perm_name, $perm_desc, & $msg, $auth = null)
	{
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		$permission = $auth->getItem($perm_name);
		if( !$permission ) {
			$permission = $auth->createPermission($perm_name);
			$permission->description = $perm_desc;
			$auth->add($permission);
			$msg = $permission->name . ' => ' . $permission->description
				. Yii::t('churros', ': permission created');
		} else if( $permission->description != $perm_desc ) {
			$permission->description = $perm_desc;
			AuthHelper::updateItem($perm_name, $permission, $auth);
			$msg = $permission->name . ' => ' . $permission->description
				. Yii::t('churros', ': permission updated');
		}
		return $permission;
	}

	static public function createOrUpdateRole($role_name, $role_desc, &$msg, $auth = null)
	{
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		$role = $auth->getItem($role_name);
		if( !$role ) {
			$role = $auth->createRole($role_name);
			$role->description = $role_desc;
			$auth->add($role);
			$msg = $role->name . ' => ' . $role->description
				. Yii::t('churros', ': role created');
		} else if( $role->description != $role_desc ) {
			$role->description = $role_desc;
			AuthHelper::updateItem($role_name, $role, $auth);
			$msg = $role->name . ' => ' . $role->description
				. Yii::t('churros', ': role updated');
		}
		return $role;
	}

	static public function updateItem($name, $item, $auth = null )
    {
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}

        if ($item->name !== $name && !$auth->supportsCascadeUpdate()) {
            $auth->db->createCommand()
                ->update($auth->itemChildTable, ['parent' => $item->name], ['parent' => $name])
                ->execute();
            $auth->db->createCommand()
                ->update($auth->itemChildTable, ['child' => $item->name], ['child' => $name])
                ->execute();
            $auth->db->createCommand()
                ->update($auth->assignmentTable, ['item_name' => $item->name], ['item_name' => $name])
                ->execute();
        }

        $item->updatedAt = time();

        $auth->db->createCommand()
            ->update($auth->itemTable, [
                'name' => $item->name,
                'description' => $item->description,
                'rule_name' => $item->ruleName,
                'data' => $item->data === null ? null : serialize($item->data),
                'updated_at' => $item->updatedAt,
            ], [
                'name' => $name,
            ])->execute();

        $auth->invalidateCache();

        return true;
    }

}
