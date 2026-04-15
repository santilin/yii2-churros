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
	static protected array $messages = [];

	static public function clearMessages(): void
	{
		static::$messages = [];
	}

	static public function addMessage(string $message): void
	{
		static::$messages[] = $message;
	}

	static public function echoMessage(bool $verbose, string $eol = "\n")
	{
		foreach (static::$messages as $message) {
			if (trim($message) !== '') {
				if ($verbose || $message[0] !== '=') {
					echo $message . $eol;
				}
			}
		}
	}

	static public function getMessage(): array
	{
		return array_map(fn($m) => trim($m), static::$messages);
	}

	static public function getMessagesAsString(string $eol = "\n"): string
	{
		return implode($eol, static::getMessage());
	}

	static public function createOrUpdatePermission(string $perm_name, string $perm_desc,
													bool $is_default = false, $auth = null)
	{
		if ($auth == null) {
			$auth = \Yii::$app->authManager;
		}
		$permission = $auth->getPermission($perm_name);
		if (!$permission) {
			$permission = $auth->createPermission($perm_name);
			$permission->description = $perm_desc;
			if ($is_default) {
				$permission->createdAt = 0;
			}
			$auth->add($permission);
			static::addMessage('+ `' . $permission->name . '` => ' . $permission->description
				. ': ' . Yii::t('churros', 'permission created'));
		} else if ($permission->description != $perm_desc) {
			$permission->description = $perm_desc;
			$auth->update($perm_name, $permission);
			static::addMessage('^ `' . $permission->name . '` => ' . $permission->description
				. ': ' . Yii::t('churros', 'permission updated'));
		} else {
			if ($is_default) {
				$auth->db->createCommand()->update(
					$auth->itemTable, ['created_at' => 0], ['name' => $perm_name])->execute();
			}
			static::addMessage('= `' . $permission->name . '`, ' . $permission->description . ': ' . Yii::t('churros', 'permission already exists'));
		}
		return $permission;
	}

	static public function createOrUpdateRole(string $role_name, string $role_desc,
											 bool $is_default = false, $auth = null): Role
	{
		if ($auth == null) {
			$auth = \Yii::$app->authManager;
		}
		$role = $auth->getRole($role_name);
		if (!$role) {
			$perm = $auth->getPermission($role_name);
			if ($perm) {
				throw new \Exception("$role_name: role already exists as a permission");
			}
			$role = $auth->createRole($role_name);
			$role->description = $role_desc;
			if ($is_default) {
				$role->createdAt = 0;
			}
			$auth->add($role);
			static::addMessage('+ `' . $role->name . '` => ' . $role->description
				. ': ' . Yii::t('churros', 'role created'));
		} else if ($role->description != $role_desc) {
			$role->description = $role_desc;
			$auth->update($role_name, $role);
			static::addMessage('^ `' . $role->name . '` => ' . $role->description
				. ': ' . Yii::t('churros', 'role updated'));
		} else {
			if ($is_default) {
				$auth->db->createCommand()->update(
					$auth->itemTable, ['created_at' => 0], ['name' => $role_name])->execute();
			}
			static::addMessage('= `' . $role->name . '`, ' . $role->description . ': ' . Yii::t('churros', 'role already exists'));
		}
		return $role;
	}

    static public function addPermissionsToRole($role_name, array|string $perm_names, $auth = null)
    {
		if ($auth == null) {
			$auth = \Yii::$app->authManager;
		}
		if (is_string($role_name)) {
			$role = $auth->getRole($role_name);
		} else {
			$role = $role_name;
		}
		if (!$role) {
			throw new \Exception( "$role_name: role not found" );
		}
		foreach ((array)$perm_names as $perm_name) {
			$perm = $auth->getItem($perm_name);
			if (!$perm) {
				static::addMessage("x `$perm_name`: permission or role not found");
				continue;
			}
			if (!$auth->hasChild($role, $perm)) {
				$auth->addChild($role, $perm);
				if ($perm->type == Item::TYPE_ROLE) {
					static::addMessage('+ `' . $perm_name . '`: role added to role `' . $role->name . '`');
				} else {
					static::addMessage('+ `' . $perm_name . '`: permission added to role `' . $role->name . '`');
				}
			} else {
				static::addMessage('= `' . $perm_name . '`: permission or role already assigned to role `' . $role->name . '`');
			}
		}
		return $role;
    }

    static public function createPermissions(array $perms, $auth = null)
    {
		if ($auth == null) {
			$auth = \Yii::$app->authManager;
		}
		foreach( $perms as $perm_name => $perm_desc) {
			if (is_int($perm_name)) {
				$perm_name = $perm_desc;
			}
			AuthHelper::createOrUpdatePermission($perm_name, $perm_desc, $auth);
		}
    }

    static public function createRoles(array $roles, $auth = null)
    {
		if ($auth == null) {
			$auth = \Yii::$app->authManager;
		}
		foreach( $roles as $role_name => $role_desc) {
			AuthHelper::createOrUpdateRole($role_name, $role_desc, false, $auth);
		}
    }

    static public function assignToUser(array|int|string $user_id_or_names, array|string $perms, $auth = null)
    {
		if ($auth == null) {
			$auth = \Yii::$app->authManager;
		}
		foreach ( (array)$user_id_or_names as $user_id_or_name) {
			if (is_numeric($user_id_or_name)) {
				$user_name = $user_id = $user_id_or_name;
			} else {
				$class = Yii::$app->user->identityClass;
				$identity = $class::find()->whereUserName($user_id_or_name)->one();
				if ($identity == null) {
					throw new \Exception( "$user_id_or_name: user not found" );
				}
				$user_id = $identity->id;
				$user_name = $user_id_or_name;
			}
			foreach ((array) $perms as $perm_name) {
				if ($perm_name instanceof Role) {
					$perm_name = $perm_name->name;
				}
				$perm = $auth->getItem($perm_name);
				if (!$perm) {
					static::addMessage("x `$perm_name`: permission or role not found");
					continue;
				}
				if (!$auth->getAssignment($perm_name, $user_id)) {
					$auth->assign($perm, $user_id);
					if ($perm->type == Item::TYPE_ROLE) {
						static::addMessage('+ `' . $perm_name . '`: role assigned to user `' . $user_name . '`');
					} else {
						static::addMessage('+ `' . $perm_name . '`: permission assigned to user `' . $user_name . '`');
					}
				} else {
					if ($perm->type == Item::TYPE_ROLE) {
						static::addMessage('= `' . $perm_name . '`: role already assigned to user `' . $user_name . '`');
					} else {
						static::addMessage('= `' . $perm_name . '`: permission already assigned to user `' . $user_name . '`');
					}
				}
			}
		}
    }

	static public function revokeFromUser($user_id_or_name, array $perms, $auth = null)
	{
		if ($auth == null) {
			$auth = \Yii::$app->authManager;
		}
		if (is_numeric($user_id_or_name)) {
			$user_name = $user_id = $user_id_or_name;
		} else {
			$class = Yii::$app->user->identityClass;
			$identity = $class::find()->whereUserName($user_id_or_name)->one();
			if ($identity == null) {
				throw new \Exception("$user_id_or_name: user not found");
			}
			$user_id = $identity->id;
			$user_name = $user_id_or_name;
		}
		foreach ($perms as $perm_name) {
			if ($perm_name instanceof Role) {
				$perm_name = $perm_name->name;
			}
			$perm = $auth->getItem($perm_name);
			if (!$perm) {
				static::addMessage("x `$perm_name`: permission or role not found");
			}
			$assignment = $auth->getAssignment($perm_name, $user_id);
			if ($assignment) {
				$auth->revoke($perm, $user_id);
				if ($perm->type == Item::TYPE_ROLE) {
					static::addMessage('- `' . $perm_name . '`: role revoked from user `' . $user_name . '`');
				} else {
					static::addMessage('- `' . $perm_name . '`: permission revoked from user `' . $user_name . '`');
				}
			} else {
				if ($perm->type == Item::TYPE_ROLE) {
					static::addMessage('= `' . $perm_name . '`: role was not assigned to user `' . $user_name . '`');
				} else {
					static::addMessage('= `' . $perm_name . '`: permission was not assigned to user `' . $user_name . '`');
				}
			}
		}
	}


    static public function removeFromRole($role_name, array|string $perm_names, $auth = null)
    {
		if ($auth == null) {
			$auth = \Yii::$app->authManager;
		}
		$parent = $auth->getItem($role_name);
		if ($parent == null) {
			static::addMessage("= Role `$role_name` not found");
			return;
		}
		$perm_names = (array)$perm_names;
		foreach ($perm_names as $perm_name) {
			$child = $auth->getItem($perm_name);
			if ($child == null) {
				static::addMessage("= Permission `$perm_name` not found in role `$role_name`");
				continue;
			}
			if ($auth->removeChild($parent, $child)) {
				static::addMessage("- Permission `$perm_name` removed from role `$role_name`");
			} else {
				static::addMessage("= Permission `$perm_name` not found in role `$role_name`");
			}
		}
	}

	static public function removeRoles(array $role_names, $auth = null)
    {
		if ($auth == null) {
			$auth = \Yii::$app->authManager;
		}
		foreach ($role_names as $role_name) {
			$role = $auth->getItem($role_name);
			if ($role == null) {
				static::addMessage("= Role `$role_name` not found");
			} else if ($auth->remove($role)) {
				static::addMessage("- Role `$role_name` removed");
			}
		}
	}

	static public function removePerms(array $perm_names, $auth = null)
    {
		if ($auth == null) {
			$auth = \Yii::$app->authManager;
		}
		foreach ($perm_names as $perm_name) {
			$perm = $auth->getItem($perm_name);
			if ($perm == null) {
				static::addMessage("x `$perm_name`: permission not found");
			} else if ($auth->remove($perm)) {
				static::addMessage("- Permission `$perm_name` removed");
			}
		}
	}

} // class AuthHelper


