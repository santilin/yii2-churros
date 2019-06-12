<?php
/**
 */

namespace santilin\Churros\commands;

use Yii;
use yii\console\Controller;
use app\models\Tarea;

/**
 * Telegram console commands
 *
 *
 * @author Santilín <santi@noviolento.es>
 * @since 1.0
 */
class TelegramController extends Controller
{
	/** The version of this command */
	const VERSION = '0.1';


	/**
     * Reads updates
     *
     */
    public function actionUpdates()
    {
		$updates = \Yii::$app->telegram->getUpdates();
		foreach( $updates->result as $update ) {
			if ( isset($update->callback_query) ) {
				$data = $update->callback_query->data;
				$parts = explode("/", $data);
				$tarea = Tarea::findOne(intval($parts[3]));
				$tarea->acabar();
			}
		}
	}

	public function actionRecordarTareas()
	{
		$filteredQuery = Tarea::find()->activas()->muy_prioritarias()->ordenPrioridad();
		$muyprioritarias = $filteredQuery->all();
		if( count($muyprioritarias) > 0 ) {
			\Yii::$app->telegram->sendMessage([
				'chat_id' => Yii::$app->params['telegram.chat_id'],
				'text' => "Iniciando el recordatorio de tareas"
			]);
		}
		foreach ( $muyprioritarias as $tarea ) {
			$tarea->telegram("acabar", $tarea->id);
		}
	}

} // class


