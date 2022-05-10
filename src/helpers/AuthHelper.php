<?php
/**
 * @link
 * @copyright
 * @license
 * @todo prunePermissions(), removeModelPermissions($model_names, $force)
 */

namespace santilin\churros\helpers;

use Yii;
use yii\rbac\Item;

class AuthHelper
{
	static public function createOrUpdatePermission($perm_name, $perm_desc, & $msg, $auth = null)
	{
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		$msg = '';
		$permission = $auth->getPermission($perm_name);
		if( !$permission ) {
			$permission = $auth->createPermission($perm_name);
			$permission->description = $perm_desc;
			$auth->add($permission);
			$msg = $permission->name . ' => ' . $permission->description
				. ': ' . Yii::t('churros', 'permission created');
		} else if( $permission->description != $perm_desc ) {
			$permission->description = $perm_desc;
			$auth->update($perm_name, $permission);
			$msg = $permission->name . ' => ' . $permission->description
				. ': ' . Yii::t('churros', 'permission updated');
		}
		return $permission;
	}

	static public function createOrUpdateRole($role_name, $role_desc, &$msg, $auth = null)
	{
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		$msg = '';
		$role = $auth->getRole($role_name);
		if( !$role ) {
			$role = $auth->createRole($role_name);
			$role->description = $role_desc;
			$auth->add($role);
			$msg = $role->name . ' => ' . $role->description
				. ': ' . Yii::t('churros', 'role created');
		} else if( $role->description != $role_desc ) {
			$role->description = $role_desc;
			$auth->update($role_name, $role);
			$msg = $role->name . ' => ' . $role->description
				. ': ' . Yii::t('churros', 'role updated');
		}
		return $role;
	}

    static public function addToRole($role, array $perm_names, string &$msg, $auth = null)
    {
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		$msgs = [];
		foreach( $perm_names as $perm_name ) {
			$perm = $auth->getItem($perm_name);
			if( !$perm ) {
				throw new \Exception( "$perm_name: permission or role not found" );
			}
			if( !$auth->hasChild($role, $perm) ) {
				$auth->addChild($role, $perm);
				if( $perm->type == Item::TYPE_ROLE ) {
					$msgs[] = "role $perm_name added to role {$role->name}";
				} else {
					$msgs[] = "permission $perm_name added to role {$role->name}";
				}
			}
		}
		$msg = join("\n", $msgs);
		return $role;
    }

    static public function createPermissions(array $perms, string &$msg, $auth = null)
    {
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		$msgs = [];
		foreach( $perms as $perm_name => $perm_desc ) {
			$perm = AuthHelper::createOrUpdatePermission($perm_name,
				$perm_desc, $msg, $auth);
			if( $msg ) $msgs[] = $msg;
		}
		$msg = join("\n", $msgs);
    }

    static public function createRoles(array $roles, string &$msg, $auth = null)
    {
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		$msgs = [];
		foreach( $roles as $role_name => $role_desc ) {
			$role = AuthHelper::createOrUpdateRole($role_name,
				$role_desc, $msg, $auth);
			if( $msg ) $msgs[] = $msg;
		}
		$msg = join("\n", $msgs);
    }

    static public function assignToUser($user_id_or_name, array $perms, string &$msg, $auth = null)
    {
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		if( is_int($user_id_or_name) ) {
			$user_name = $user_id = $user_id_or_name;
		} else {
            $class = Yii::$app->user->identityClass;
            $identity = $class::find()->whereUserName($user_id_or_name)->one();
            if( $identity == null ) {
				throw new \Exception( "$user_id_or_name: user not found" );
            }
			$user_id = $identity->id;
			$user_name = $user_id_or_name;
		}
		$msgs = [];
		foreach( $perms as $perm_name ) {
			$perm = $auth->getItem($perm_name);
			if( !$perm ) {
				throw new \Exception( "$perm_name: permission or role not found" );
			}
			if( !$auth->getAssignment($perm_name, $user_id) ) {
				$auth->assign($perm, $user_id);
				if( $perm->type == Item::TYPE_ROLE ) {
					$msgs[] = "role $perm_name added to user $user_name";
				} else {
					$msgs[] = "permission $perm_name added to role $user_name";
				}
			}
			if ($msg ) $msgs[] = $msg;
		}
		$msg = join("\n", $msgs);
    }

} // class AuthHelper


