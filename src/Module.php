<?php
namespace santilin\churros;

use Yii;
use yii\helpers\Inflector;

class Module extends \yii\base\Module
{
	public $controllerNamespace = 'santilin\churros\controllers';

	public function init()
	{
		parent::init();
		if (Yii::$app instanceof \yii\console\Application) {
			$this->controllerNamespace = 'santilin\churros\console\controllers';
			$this->registerConsoleControllers();
		}

	}

	/**
	 * Exposes the churros console controllers via controllerMap instead of
	 * letting Yii auto-discover them by scanning the directory, and skips
	 * those the application itself already extends under its own
	 * controllerNamespace (e.g. app\console\controllers\AuthController
	 * extends santilin\churros\console\controllers\AuthController) -- otherwise,
	 * they appear duplicated in `./yii help` under `auth/*` and `churros/auth/*`
	 * with the same code, and if the app overrides any action (such as
	 * actionUserPerms), the churros version gets out of sync and remains
	 * invocable under the old name.
	 *
	 * A controller is only skipped when there is a real inheritance
	 * relationship, not on a mere class name match (e.g. the app may have its
	 * own DevelController unrelated to the churros one: that one is not skipped).
	 */
	protected function registerConsoleControllers(): void
	{
		$dir = Yii::getAlias('@' . str_replace('\\', '/', $this->controllerNamespace));
		if (!is_dir($dir)) {
			return;
		}
		$appNamespace = Yii::$app->controllerNamespace;
		foreach (glob($dir . '/*Controller.php') ?: [] as $file) {
			$className = basename($file, '.php');
			$churrosClass = $this->controllerNamespace . '\\' . $className;
			if (!class_exists($churrosClass)) {
				continue;
			}
			$appClass = $appNamespace . '\\' . $className;
			if (class_exists($appClass) && is_subclass_of($appClass, $churrosClass)) {
				continue;
			}
			$id = Inflector::camel2id(substr($className, 0, -10));
			$this->controllerMap[$id] = $churrosClass;
		}
		// With controllerMap already explicitly populated, keep Yii from also
		// scanning the directory on its own (it would list again, under the
		// same id, exactly those we just decided to expose or hide).
		$this->setControllerPath('@runtime/.churros-console-controllers-hidden');
	}

} // class Module
