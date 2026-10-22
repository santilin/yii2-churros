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
	 * Expone los controladores de consola de churros vía controllerMap en vez
	 * de dejar que Yii los autodescubra escaneando el directorio, y se salta
	 * los que la propia aplicación ya extiende bajo su propio
	 * controllerNamespace (p.ej. app\console\controllers\AuthController
	 * extends santilin\churros\console\controllers\AuthController) -- si no,
	 * aparecen duplicados en `./yii help` bajo `auth/*` y `churros/auth/*`
	 * con el mismo código, y si la app sobreescribe alguna acción (como
	 * actionUserPerms), la versión de churros queda desincronizada y sigue
	 * siendo invocable con el nombre viejo.
	 *
	 * Solo se excluye cuando hay relación de herencia real, no por mera
	 * coincidencia de nombre de clase (p.ej. la app puede tener su propio
	 * DevelController sin relación con el de churros: ese no se excluye).
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
		// Con controllerMap ya poblado explícitamente, que Yii no escanee
		// además el directorio por su cuenta (volvería a listar, con el
		// mismo id, justo los que acabamos de decidir exponer u ocultar).
		$this->setControllerPath('@runtime/.churros-console-controllers-hidden');
	}

} // class Module
