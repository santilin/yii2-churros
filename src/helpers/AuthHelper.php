<?php
/**
 * @link
 * @copyright
 * @license
 * @todo prunePermissions(), removeModelPermissions($model_names, $force)
 */

namespace santilin\churros\helpers;

use Yii;
use yii\rbac\{Item,Role};

class AuthHelper
{
	static protected $lastMessage = '';

	static public function echoLastMessage($eol = "\n")
	{
		if( trim(static::$lastMessage) != '' ) {
			echo static::$lastMessage . $eol;
		}
	}

	static public function getLastMessage()
	{
		return trim(self::$lastMessage);
	}

	static public function createOrUpdatePermission($perm_name, $perm_desc, $auth = null)
	{
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		static::$lastMessage = '';
		$permission = $auth->getPermission($perm_name);
		if( !$permission ) {
			$permission = $auth->createPermission($perm_name);
			$permission->description = $perm_desc;
			$auth->add($permission);
			static::$lastMessage = '+ ' . $permission->name . ' => ' . $permission->description
				. ': ' . Yii::t('churros', 'permission created');
		} else if( $permission->description != $perm_desc ) {
			$permission->description = $perm_desc;
			$auth->update($perm_name, $permission);
			static::$lastMessage = '^ ' . $permission->name . ' => ' . $permission->description
				. ': ' . Yii::t('churros', 'permission updated');
		} else {
			static::$lastMessage = '= ' . "{$permission->name}, {$permission->description}: " . Yii::t('churros', 'permission already exists');
		}
		return $permission;
	}

	static public function createOrUpdateRole($role_name, $role_desc, $auth = null)
	{
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		static::$lastMessage = '';
		$role = $auth->getRole($role_name);
		if( !$role ) {
			$role = $auth->createRole($role_name);
			$role->description = $role_desc;
			$auth->add($role);
			static::$lastMessage = '+ ' . $role->name . ' => ' . $role->description
				. ': ' . Yii::t('churros', 'role created');
		} else if( $role->description != $role_desc ) {
			$role->description = $role_desc;
			$auth->update($role_name, $role);
			static::$lastMessage = '^ ' . $role->name . ' => ' . $role->description
				. ': ' . Yii::t('churros', 'role updated');
		} else {
			static::$lastMessage = '= ' . "{$role->name}, {$role->description}: " . Yii::t('churros', 'role already exists');
		}
		return $role;
	}

    static public function addToRole($role_name, array $perm_names, $auth = null)
    {
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		$msgs = [];
		if( is_string($role_name) ) {
			$role = $auth->getRole($role_name);
		} else {
			$role = $role_name;
		}
		if( !$role ) {
			throw new \Exception( "$role_name: role not found" );
		}
		foreach ($perm_names as $perm_name) {
			$perm = $auth->getItem($perm_name);
			if( !$perm ) {
				$msgs[] = '= ' . "$perm_name: permission doesn't exist";
				continue;
			}
			if( !$auth->hasChild($role, $perm) ) {
				$auth->addChild($role, $perm);
				if( $perm->type == Item::TYPE_ROLE ) {
					$msgs[] = '+ ' . "$perm_name: role added to role {$role->name}";
				} else {
					$msgs[] = '+ ' . "$perm_name: permission added to role {$role->name}";
				}
			} else {
				$msgs[] = '= ' . "$perm_name: permission already assigned to role {$role->name}";
			}
		}
		static::$lastMessage = join("\n", $msgs);
		return $role;
    }

    static public function createPermissions(array $perms, $auth = null)
    {
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		$msgs = [];
		foreach( $perms as $perm_name => $perm_desc ) {
			if( is_int($perm_name) ) {
				$perm_name = $perm_desc;
			}
			$perm = AuthHelper::createOrUpdatePermission($perm_name,
				$perm_desc, $auth);
			if( static::$lastMessage ) $msgs[] = static::$lastMessage;
		}
		static::$lastMessage = join("\n", $msgs);
    }

    static public function createRoles(array $roles, $auth = null)
    {
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		$msgs = [];
		foreach( $roles as $role_name => $role_desc ) {
			$role = AuthHelper::createOrUpdateRole($role_name,
				$role_desc, static::$lastMessage, $auth);
			if( static::$lastMessage ) $msgs[] = static::$lastMessage;
		}
		static::$lastMessage = join("\n", $msgs);
    }

    static public function assignToUser($user_id_or_name, array $perms, $auth = null)
    {
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		if( is_numeric($user_id_or_name) ) {
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
		foreach ($perms as $perm_name) {
			if ($perm_name instanceof Role) {
				$perm_name = $perm_name->name;
			}
			$perm = $auth->getItem($perm_name);
			if( !$perm ) {
				throw new \Exception( "$perm_name: permission or role not found" );
			}
			if( !$auth->getAssignment($perm_name, $user_id) ) {
				$auth->assign($perm, $user_id);
				if( $perm->type == Item::TYPE_ROLE ) {
					$msgs[] = '+ ' . "role $perm_name assigned to user $user_name";
				} else {
					$msgs[] = '+ ' . "permission $perm_name assinged to role $user_name";
				}
			} else {
				if( $perm->type == Item::TYPE_ROLE ) {
					$msgs[] = '= ' . "$perm_name: role already assigned to user $user_name";
				} else {
					$msgs[] = '= ' . "$perm_name: permission already assigned to role $user_name";
				}
			}
		}
		static::$lastMessage = join("\n", $msgs);
    }

    static public function removeFromRole($role_name, array|string $perm_names, $auth = null)
    {
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		$perm_names = (array)$perm_names;
		foreach ($perm_names as $perm_name) {
			$child = $auth->getItem($perm_name);
			if( $child == null ) {
				continue;
			}
			$parent = $auth->getItem($role_name);
			if( $parent == null ) {
				continue;
			}
			if ($auth->removeChild($parent, $child)) {
				static::$lastMessage = "- $perm_name removed from role $role_name";
			} else {
				static::$lastMessage = "= $perm_name not found in role $role_name";
			}
		}
	}

	static public function removeRoles(array $role_names, $auth = null)
    {
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		foreach( $role_names as $role_name ) {
			$role = $auth->getItem($role_name);
			if( $role == null ) {
				static::$lastMessage = "= `$role_name` role not found";
			} else if ($auth->remove($role)) {
				static::$lastMessage = "- `$role_name` role removed";
			}
		}
	}

	static public function removePerms(array $perm_names, $auth = null)
    {
		if( $auth == null ) {
			$auth = \Yii::$app->authManager;
		}
		foreach( $perm_names as $perm_name ) {
			$role = $auth->getItem($perm_name);
			if( $role == null ) {
				static::$lastMessage = "= `$perm_name` permission not found";
			} else if ($auth->remove($role)) {
				static::$lastMessage = "- `$perm_name` permission removed";
			}
		}
	}

} // class AuthHelper


