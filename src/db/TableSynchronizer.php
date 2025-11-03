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
		public Query|string|null $where = null,
		public int $limit = 0,
	) {}

	protected function createSourceQuery(): Query
	{
		if (is_string($this->where)) {
			$sourceQuery = (new Query())
				->select('*')
				->from($this->tblOrigen)
				->where($this->where);
		} else {
			$sourceQuery = $this->where;
			if (empty($sourceQuery->from)) {
				$sourceQuery->from($this->tblOrigen);
			}
		}
		if ($this->limit > 0 && $sourceQuery->limit == 0) {
			$sourceQuery->limit($this->limit);
		}
		return $sourceQuery;
	}

	public function syncronize(array $fields_match = [])
	{
		$sourceQuery = $this->createSourceQuery();
		$schema_origen = $this->dbOrigen->getTableSchema($this->tblOrigen);
		$dest_scheme = $this->dbDest->getTableSchema($this->tblDest);
		$sourceRecords = $sourceQuery->all($this->dbOrigen);
		$count_result = $this->dbDest->createCommand("SELECT COUNT(*) FROM {$this->tblDest}")->queryOne();
		$dest_count = intval(reset($count_result));
		echo "Overwriting $dest_count records into $this->tblDest\n";
		echo "Read " . count($sourceRecords) . " records from $this->tblOrigen\n";

	}

	public function overwrite()
	{
		$sourceQuery = $this->createSourceQuery();
		$dest_scheme = $this->dbDest->getTableSchema($this->tblDest);
		$sourceRecords = $sourceQuery->all($this->dbOrigen);
		$result = $this->dbDest->createCommand("SELECT COUNT(*) FROM {$this->tblDest}")->queryOne();
		$dest_count = intval(reset($result));
		echo "Overwriting $dest_count records into $this->tblDest\n";
		echo "Read " . count($sourceRecords) . " records from $this->tblOrigen\n";

		// Preprocesar PKs destino
		$destPk = $dest_scheme->primaryKey;
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
			foreach (array_keys($record) as $fname) {
				if (!$dest_scheme->getColumn($fname)) {
					unset($record[$fname]);
				}
			}

			// Almacenar PKs para eliminación posterior
			$sourcePkValues[] = $pk_conds;

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
				if (intval($this->dbDest->createCommand()
					->insert($this->tblDest, $record)
					->execute()) == 0) {
					throw new \Exception("No se ha podido insertar el registro en {$this->tblDest}\n");
				}
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
