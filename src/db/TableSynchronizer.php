<?php

namespace santilin\churros\db;

use Yii;
use yii\db\{Connection,Query};

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
		$query = (new Query())
			->select('*')
			->from($this->tblOrigen);

		if ($this->where) {
			$query->where($this->where);
		}

		if ($this->limit > 0) {
			$query->limit($this->limit);
		}

		$schema_origen = $this->dbOrigen->getTableSchema($this->tblOrigen);
		$schema_destino = $this->dbDest->getTableSchema($this->tblDest); // Nuevo: esquema destino
		$sourceRecords = $query->all($this->dbOrigen);
		$result = $this->dbDest->createCommand("SELECT COUNT(*) FROM {$this->tblDest}")->queryOne();
		$dest_count = intval(reset($result));
		echo "Syncronizing $dest_count records into $this->tblDest\n";
		echo "Read " . count($sourceRecords) . " records from $this->tblOrigen\n";

		// Preprocesar PKs destino
		$destPk = $schema_destino->primaryKey;
		$sourcePkValues = [];
		$existing_count = $new_count = 0;

		foreach ($sourceRecords as $record) {
			$pk_conds = [];
			foreach ($destPk as $pk) { // Usar PKs del destino
				if (!isset($record[$pk])) {
					throw new \Exception("Columna PK '$pk' no existe en registro origen");
				}
				$pk_conds[$pk] = $record[$pk];
			}

			// Almacenar PKs para eliminación posterior
			$sourcePkValues[] = array_values($pk_conds);

			// Verificar existencia usando PKs destino
			$existingRecord = (new Query())
				->from($this->tblDest)
				->where($pk_conds)
				->one($this->dbDest);

			if ($existingRecord) {
				$existing_count++;
				$this->dbDest->createCommand()
				->update($this->tblDest, $record, $pk_conds)
				->execute();
			} else {
				$new_count++;
				$this->dbDest->createCommand()
				->insert($this->tblDest, $record)
				->execute();
			}
		}

		// Eliminación segura con claves compuestas
		if (count($sourcePkValues) && !empty($destPk)) {
			$deleted_count = $this->dbDest->createCommand()
				->delete($this->tblDest, ['NOT IN', $destPk, $sourcePkValues])
				->execute();
		}
		echo "Inserted " . $new_count . " records into {$this->tblDest}\n";
		echo "Updated " . $existing_count . " records in {$this->tblDest}\n";
		echo "Deleted " . $deleted_count . " records from {$this->tblDest}\n";

		return count($sourceRecords);
	}
}
