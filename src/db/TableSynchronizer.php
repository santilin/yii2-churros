<?php

namespace santilin\churros\db;

use Yii;
use yii\db\{Connection,Query};

class TableSynchronizer
{
	public function __construct(
		public Connection $dbSource,
		public Connection $dbDest,
		public string $tblSource,
		public string $tblDest,
		public Query|string|null $where = null,
		public int $limit = 0,
		public bool $verbose = true,
	) {}

	protected function createSourceQuery(array $keys = []): Query
	{
		if (is_string($this->where)) {
			$source_query = (new Query())
				->select('*')
				->from($this->tblSource)
				->where($this->where);
		} else {
			$source_query = $this->where;
			if (empty($source_query->from)) {
				$source_query->from($this->tblSource);
			}
		}
		foreach ($keys as $key) {
			$source_query->orderBy([$key => SORT_ASC]);
		}
		if ($this->limit > 0 && $source_query->limit == 0) {
			$source_query->limit($this->limit);
		}
		return $source_query;
	}

	public function synchronize(array $keys_match, array $fields_match = [],
								callable $before_save = null, callable $before_delete = null): array
	{
		$source_query = $this->createSourceQuery(array_values($keys_match));
		$source_records = $source_query->all($this->dbSource);
		$dest_scheme = $this->dbDest->getTableSchema($this->tblDest);
		$dest_pks = $dest_scheme->primaryKey;
		if ($this->verbose) {
			echo "Read " . count($source_records) . " records from $this->tblSource\n";
		}

		$source_records_pks = [];
		$existing_count = $new_count = $deleted_count = 0;

		foreach ($source_records as $source_record) {
			$dest_conds = [];
			foreach ($keys_match as $dest_pk => $source_pk) {
				if (!isset($source_record[$source_pk])) {
					throw new \Exception("$source_pk: primary key column not found in source record");
				}
				if (!$dest_scheme->getColumn($dest_pk)) {
					throw new \Exception("$dest_pk: primary key column not found in dest record");
				}
				$dest_conds[$dest_pk] = $source_record[$source_pk];
			}

			// store pks to be deleted at the end
			$source_records_pks[] = $dest_conds;

			$existingRecord = (new Query())
				->from($this->tblDest)
				->where($dest_conds)
				->one($this->dbDest);

			$destRecord = [];
			foreach ($keys_match as $dest_pk => $source_pk) {
				$destRecord[$dest_pk] = $source_record[$source_pk];
			}
			if (count($keys_match) > 1) {
				$dest_index = json_encode($destRecord);
			} else {
				$dest_index = array_values($destRecord)[0];
			}
			foreach ($fields_match as $fld => $value) {
				if (!$dest_scheme->getColumn($fld)) {
					throw new \Exception("$fld: field not found in des table: {$this->tblDest}");
				}
				if (is_callable($value)) {
					$destRecord[$fld] = call_user_func($value, $source_record);
				} else {
					$destRecord[$fld] = $source_record[$value];
				}
			}
			if ($before_save) {
				$destRecord = call_user_func($before_save, $destRecord, $existingRecord === null);
				if ($destRecord === false) {
					continue;
				}
			}
			if ($existingRecord) {
				$existing_count++;
				$diff = array_diff_assoc($destRecord, $existingRecord);
				if ($this->verbose) {
					echo "Synchronizing existing $dest_index: " . json_encode($diff) . "\n";
				}
				if (count($diff) !== 0) {
					$changes[$dest_index] = $changes;
					$this->dbDest->createCommand()
						->update($this->tblDest, $destRecord, $dest_conds)
						->execute();
				}
			} else {
				$new_count++;
				if ($this->verbose) {
					echo "Inserting $dest_index: " . json_encode($destRecord) . "\n";
				}
				$changes[$dest_index] = 'New record';
				$this->dbDest->createCommand()
					->insert($this->tblDest, $destRecord)
					->execute();
			}
		}
		// safe deletion with compound keys
		if (count($source_records_pks) && !empty($dest_pks)) {
			$delete_query = (new Query())
				->select(implode(',', $dest_pks))
				->from($this->tblDest)
				->where(['NOT IN', $dest_pks, $source_records_pks]);
			foreach ($delete_query->all() as $to_delete) {
				if ($before_delete) {
					if (!call_user_func($before_delete, $to_delete)) {
						continue;
					}
				}
				if (count($dest_pks) > 1) {
					throw new \Exception('TODO');
				} else {
					$dest_index = $to_delete[$dest_pks[0]];
				}
				$changes[$dest_index] = 'Deleted';
				$pk_condition = [];
				foreach ($dest_pks as $pk_field) {
					$pk_condition[$pk_field] = $to_delete[$pk_field];
				}
				$this->dbDest->createCommand()
					->delete($this->tblDest, $pk_condition)
					->execute();
				$deleted_count++;
			}
		}
		return $changes;
	}

	public function overwrite()
	{
		$dest_scheme = $this->dbDest->getTableSchema($this->tblDest);
		$dest_pks = $dest_scheme->primaryKey;
		$source_query = $this->createSourceQuery();
		$source_records = $source_query->all($this->dbSource);
		$result = $this->dbDest->createCommand("SELECT COUNT(*) FROM {$this->tblDest}")->queryOne();
		$dest_count = intval(reset($result));
		echo "Overwriting $dest_count records into $this->tblDest\n";
		echo "Read " . count($source_records) . " records from $this->tblSource\n";

		// Preprocess dest pks
		$source_records_pks = [];
		$existing_count = $new_count = 0;

		foreach ($source_records as $source_record) {
			$dest_conds = [];
			foreach ($dest_pks as $pk) { // Usar PKs del destino
				if (!isset($source_record[$pk])) {
					throw new \Exception("$pk: primary key column not found in source record");
				}
				$dest_conds[$pk] = $source_record[$pk];
			}
			foreach (array_keys($source_record) as $fname) {
				if (!$dest_scheme->getColumn($fname)) {
					unset($source_record[$fname]);
				}
			}

			// store pks to be deleted at the end
			$source_records_pks[] = $dest_conds;

			$existingRecord = (new Query())
				->from($this->tblDest)
				->where($dest_conds)
				->one($this->dbDest);

			if ($existingRecord) {
				$existing_count++;
				$this->dbDest->createCommand()
					->update($this->tblDest, $source_record, $dest_conds)
					->execute();
			} else {
				$new_count++;
				if (intval($this->dbDest->createCommand()
					->insert($this->tblDest, $source_record)
					->execute()) == 0) {
					throw new \Exception("Error saving record in {$this->tblDest}: " . json_encode($source_record));
				}
			}
		}

		// safe deletion with compound keys
		if (count($source_records_pks) && !empty($dest_pks)) {
			$deleted_count = $this->dbDest->createCommand()
				->delete($this->tblDest, ['NOT IN', $dest_pks, $source_records_pks])
				->execute();
		}
		echo "Inserted " . $new_count . " records into {$this->tblDest}\n";
		echo "Updated " . $existing_count . " records in {$this->tblDest}\n";
		echo "Deleted " . $deleted_count . " records from {$this->tblDest}\n";

		return count($source_records);
	}
}
