<?php

namespace santilin\churros\db;

use yii\db\Connection;
use yii\db\Query;

class TableSynchronizer
{
	public function __construct(
		public Connection $dbOrigen,
		public Connection $dbDest,
		public string $tblOrigen,
		public string $tblDest,
		public string $where = '',
		public int $limit = 0
	) {}

	public function synchronize()
	{
		// 1. Obtener registros de la tabla origen
		$query = (new Query())
		->select('*')
		->from($this->tblOrigen);

		if ($this->where) {
			$query->where($this->where);
		}

		if ($this->limit > 0) {
			$query->limit($this->limit);
		}

		$sourceRecords = $query->all($this->dbOrigen);

		// 2. Procesar registros
		foreach ($sourceRecords as $record) {
			$id = $record['id'];

			// 3. Verificar existencia en destino
			$existingRecord = (new Query())
			->select('id')
			->from($this->tblDest)
			->where(['id' => $id])
			->one($this->dbDest);

			if ($existingRecord) {
				// 4. Actualizar registro existente
				$this->dbDest->createCommand()
				->update($this->tblDest, $record, ['id' => $id])
				->execute();
			} else {
				// 5. Insertar nuevo registro
				$this->dbDest->createCommand()
				->insert($this->tblDest, $record)
				->execute();
			}
		}

		// 6. Eliminar registros obsoletos (opcional, dependiendo del caso de uso)
		if (empty($this->where) && $this->limit == 0) {
			$sourceIds = array_column($sourceRecords, 'id');
			$this->dbDest->createCommand()
			->delete($this->tblDest, ['NOT IN', 'id', $sourceIds])
			->execute();
		}

		return count($sourceRecords);
	}
}
