<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace santilin\churros\console\controllers;
use Yii;
use yii\di\Instance;
use yii\base\InvalidConfigException;
use yii\db\Connection;
use yii\helpers\ArrayHelper;
use yii\console\Controller;

/**
 * Churros dump and seed commands
 *
 * @author Santilín <santi@noviolento.es>
 * @since 1.0
 */
class DbController extends Controller
{
	/** The version of this command */
	const VERSION = '0.1';

	const FORMATS = ['seeder', 'mysqldump', 'fixture', 'cvs'];
	/**
     * @var Connection|array|string the DB connection object or the application component ID of the DB connection to use
     * when applying migrations. Starting from version 2.0.3, this can also be a configuration array
     * for creating the object.
     */
    public $db = 'db';

    /** @var one of FORMATS */
    public string $format = 'seeder';

	/** @var bool Wether to add the DROP TABLE command */
	public bool $truncateTables = true;

	/** @var bool wether to create a file in seedersPath */
	public bool $createFile = true;

	/** @var string path to the output seeders directory, defaults to @app/database/seeders */
	public string $seedersPath = "@app/database/seeders";

	/** @var string path to the output fixtures directory, defaults to @app/tests/fixtures/data */
	public string $fixturesPath = "@app/database/fixtures";

	/** @var string path to the input obfuscator templates directory, defaults to @app/tests/obfjuscators */
	public string $anonymizersPath = "@app/database/anonymizers";

	/** @var int the number or records to seed or dump, defaults to 0 meaning all */
	public int $count = 0;

	/** @var bool the number or records to seed or dump, defaults to 0 meaning all */
	public bool $anonymize = false;

	/** @var string the where clause to filter records */
	public ?string $where = null;

    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['db', 'format', 'truncateTables','createFile','seedersPath','fixturesPath','count', 'where', 'anonymize']
        );
    }

    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
			'a' => 'anonymize',
            'c' => 'createFile',
            'f' => 'format',
            'n' => 'count',
            'p' => 'seedersPath',
            't' => 'truncateTables',
            'x' => 'fixturesPath',
		]);
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * @param \yii\base\Action $action the action to be executed.
     * @return bool whether the action should continue to be executed.
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
			if( !in_array($this->format, self::FORMATS) ) {
                throw new InvalidConfigException("{$this->format}: formato desconocido. Elige uno de " . join(",",self::FORMATS));
			}
			$this->db = Instance::ensure($this->db, Connection::className());
            return true;
        }
        return false;
    }

    protected function getPhpValue($value, string $phptype)
    {
		if (substr($phptype,0,3) == 'int' || substr($phptype,0,7) == 'tinyint'
			|| substr($phptype,0,9) == 'mediumint'
			|| substr($phptype,0,8) == 'smallint') {
			$phptype = 'integer';
		} else if (substr($phptype,0,7) == 'decimal') {
			$phptype = 'double';
		} else if (substr($phptype,0,7) == 'varchar' || substr($phptype,0,6) == 'string'
			|| substr($phptype,0,5) == 'char(' || substr($phptype,0,5) == 'enum('
			|| substr($phptype,0,4) == 'set(') {
			$phptype = 'string';
		}
		switch($phptype) {
			case 'integer':
			case 'smallint':
			case 'bigint unsigned':
			case 'float':
			case 'double':
			case 'double unsigned':
			case 'bool':
			case 'char':
			case 'boolean':
				if ($value === null && $phptype == 'boolean') {
					return false;
				} else if ($value === null) {
					return "null";
				} else if (strpos($value, ':') !== false) { // date in string format
					return "'" . strtr($value, ["\\'" => "\\\\", "'" => "\\'"]) . "'";
				} else {
					return $value;
				}
			case "string":
			case 'date':
			case 'time':
			case 'timestamp':
			case 'datetime':
			case "mediumtext":
			case 'blob':
			case 'mediumblob':
			case 'text':
				if( $value == null ) {
					return "null";
				} else {
					return "'" . strtr($value, ["\\'" => "\\\\", "'" => "\\'"]) . "'";
				}
			case 'resource':
				if( $value == null ) {
					return "null";
				} else {
					return "'" . base64_encode($value) . "'";
				}
			default:
				throw new \Exception( "Type '$phptype' not supported in Churros/DbController::getPhpValue" );
		}
    }

	/**
     * Dumps a full schema
     *
     * @param string $schemaName the schema name (optional)
     */
    public function actionDumpSchema(string $schemaName, array $tables = [])
    {
		if( $this->format != 'seeder' && $this->format != 'fixture' ) {
			throw new InvalidConfigException("{$this->format}: formato no contemplado. Sólo se contempla 'seeder' y 'fixture'");
		}
		if ($this->format == 'seeder') {
			$preamble = $this->getPreamble('dump-schema', $schemaName,
				count($tables) ? implode(',', $tables) : 'all-tables');
			$runseeder = '';
			$table_schemas = $this->db->schema->getTableSchemas($schemaName, true);
			// $table_schemas = [];
			// foreach ($table_names as $table_name) {
			// 	$table_schema = $this->db->schema->getTableSchema($table_name);
			// 	if ($table_schema->isTable())  {
			// 		$table_schemas[] = $table_schema;
			// 	}
			// }
			// print_r($table_names);die;
			$full_dump = $preamble;
			foreach ($table_schemas as $table) {
				if (!count($tables) || (count($tables) && in_array($table->name, $tables))) {
					if( $table->name != 'migration' ) {
						echo "Dumping {$table->name}\n";
						$full_dump .= $this->dumpTable($schemaName, $table);
						$runseeder .= "\t\t\$s = new {$table->name}Seeder(); \$s->run(\$db);\n";
					}
				}
			}
			$full_dump .= <<<EOF
class SchemaSeeder
{
	public function run(\$db)
	{
		\$db->createCommand()->checkIntegrity(false)->execute();
$runseeder
	}
}

EOF;
			if ($this->createFile ) {
				$write_file = true;
				@mkdir(Yii::getAlias($this->seedersPath), 0777, true);
				$filename = Yii::getAlias($this->seedersPath) . "/SchemaSeeder.php";
				if (\file_exists($filename) && !$this->confirm("The file $filename already exists. Do you want to overwrite it?") ) {
					$write_file = false;
				}
				if ($write_file) {
					\file_put_contents($filename, $full_dump);
					echo "Created seeder for schema in $filename\n";
				}
			} else {
				echo $full_dump;
			}
		} else { // fixtures
			$tables = $this->db->schema->getTableSchemas($schemaName, true);
			foreach ($tables as $table) {
				$table_dump = '';
				if( $table->name != 'migration' ) {
					echo "Dumping {$table->name}\n";
					$table_dump .= $this->dumpTable($schemaName, $table);
				}
				if ($this->createFile ) {
					$write_file = true;
					@mkdir(Yii::getAlias($this->fixturesPath), 0777, true);
					$filename = Yii::getAlias($this->fixturesPath) . "/" . $table->name . ".php";
					if (\file_exists($filename) && !$this->confirm("The file $filename already exists. Do you want to overwrite it?") ) {
						$write_file = false;
					}
					if ($write_file) {
						\file_put_contents($filename, $table_dump);
						echo "Created fixture for schema in $filename\n";
					}
				} else {
					echo $table_dump;
				}
			}
		}
	}

	/**
     * Dumps a table from a schema
     *
     * @param string $tableName the table to be dumped
     * @param string $where query filter
     */
    public function actionDumpTable(string $tableName)
    {
		$where = $this->where;
		$tableSchema = $this->db->schema->getTableSchema($tableName, true /*refresh*/);
		if ($tableSchema == null) {
			throw new \Exception("$tableName not found in database");
		}
		$schemaName = $tableSchema->schemaName??'';
		$preamble = $this->getPreamble('dump-table', $tableName, $schemaName);
		if ($this->createFile ) {
			$write_file = true;
			if ($this->format == 'seeder') {
				if (!is_dir(Yii::getAlias($this->seedersPath))) {
					mkdir(Yii::getAlias($this->seedersPath), 0777, true);
				}
				$filename = Yii::getAlias($this->seedersPath) . "/{$tableName}Seeder.php";
			} else {

				if (!is_dir(Yii::getAlias($this->fixturesPath))) {
					mkdir(Yii::getAlias($this->fixturesPath), 0777, true);
				}
				if (!is_writable(Yii::getAlias($this->fixturesPath))) {
					throw new \Exception(Yii::getAlias($this->fixturesPath) . ": not writable");
				}
				$filename = Yii::getAlias($this->fixturesPath) . "/{$tableName}.php";
			}
			if (\file_exists($filename) && !$this->confirm("The file $filename already exists. Do you want to overwrite it?") ) {
				$write_file = false;
			}
			if ($write_file) {
				\file_put_contents($filename, $preamble . $this->dumpTable($schemaName, $tableSchema, $where??''));
				echo "Created $this->format for table $tableName in $filename\n";
				if ($where) {
					echo "\twith where: $where\n";
				}
			}
		} else {
			echo $preamble . $this->dumpTable($schemaName, $tableSchema, $where);
		}
	}

	/**
	 * Seeds the specified table from the specified file or a default one [tablenameSeeder]
	 */
	public function actionSeedTable($tableName, $inputfilename = null)
	{
		switch( $this->format ) {
		case 'seeder':
			if( $inputfilename == null ) {
				$inputfilename = Yii::getAlias($this->seedersPath) . "/{$tableName}Seeder.php";
			}
			require_once($inputfilename);
			$classname = "{$tableName}Seeder";
			$class = new $classname;
			$class->run($this->db);
			break;
		case 'cvs':
			$this->seedTableFromCsv($tablename, $inputfilename);
			break;
		default:
			throw new InvalidArgumentException("seed-table: $this->format: no contemplado");
		}
	}

	/**
	 * Seeds the current schema with the specified file
	 */
	public function actionSeedSchema($inputfilename = null )
	{
		if( $inputfilename == null ) {
			$inputfilename = Yii::getAlias($this->seedersPath) . "/SchemaSeeder.php";
		}
		require_once($inputfilename);
		$s = new \SchemaSeeder;
		echo "Seeding schema from $inputfilename\n";
		$s->run($this->db);
	}

	protected function dumpTable(string $schemaName, $tableSchema, string $where = '')
	{
		switch( $this->format ) {
		case 'seeder':
			return $this->dumpTableAsSeeder($schemaName, $tableSchema, trim($where));
		case 'fixture':
			return $this->dumpTableAsFixture($schemaName, $tableSchema, trim($where));
		default:
			throw new InvalidArgumentException("dump-table: $this->format: no contemplado");
		}
	}

	protected function dumpTableAsFixture(string $schemaName, $tableSchema, string $where = null): string
    {
		$txt_data = "return [\n";
		$php_types = [];
		$column_names = [];
		$db_columns = [];
		$table_name = $tableSchema->name;

		if (Yii::$app->db->driverName == "sqlite") {
			$show_create_table = "SELECT * FROM pragma_table_xinfo('{$tableSchema->name}') WHERE hidden=0";
			$db_columns = ArrayHelper::index($this->db->createCommand($show_create_table)->queryAll(), 'name');
		} /// @todo mysql
		foreach($tableSchema->columns as $column) {
			if (count($db_columns) && !isset($db_columns[$column->name])) {
				continue;
			}
			$php_types[] = $column->dbType;
			$column_names[] = $column->name;
		}
		/**
		 * @todo quote column names
			$sql = "SELECT " . implode(',',$column_names) . " FROM ";
		*/
		$sql = "SELECT " . implode(',', array_map(function($col) {
			return '[[' . str_replace(']', ']]', $col) . ']]';
		}, $column_names));
		$sql .= ' FROM ';
		if ($schemaName) {
			$sql .= $schemaName . '.';
		}
		$sql .= "{{{$table_name}}}";
		if ($where) {
			$sql .= " WHERE $where";
		}
		if ($this->count != 0) {
			$sql .= " LIMIT {$this->count}";
		}
		$raw_data = $this->db->createCommand($sql)->queryAll();
		if ($this->anonymize) {
			$anonymizer_file = Yii::getAlias($this->anonymizersPath . '/' . $tableSchema->fullName . '.php');
			require $anonymizer_file;
			$anonymizer_function_name = '\\app\\database\\anonymizers\\' . str_replace('.', '_', $tableSchema->fullName) . '_anonymizer';
			$anonymizer_function_name($raw_data);
		}
		$nrow = 0;
		foreach ($raw_data as $row) {
			$ncolumn = 0;
			$txt_data .= "\t'{$table_name}{$nrow}' => [\n";
			foreach($row as $column) {
				$txt_data .= "\t\t'" . $column_names[$ncolumn] . '\' => ' . $this->getPhpValue($column, $php_types[$ncolumn]) . ",\n";
				$ncolumn++;
			}
			$txt_data .= "\t],\n";
			$nrow++;
		}
		$txt_data .= "];\n";
		return $txt_data;
	}


	protected function dumpTableAsSeeder(string $schemaName, $tableSchema, string $where = null): string
    {
		$txt_data = '';
		$php_types = [];
		$columna_names = [];
		$table_name = $tableSchema->name;

// 		Yii::$app->db->createCommand("USE $schemaName")->execute();
		$ret = "\nclass {$table_name}Seeder {\n";
		$ret .= "\n";
		$ret .= "\t/* columns */\n";
		$ret .= "\tprivate \$columns = [\n";

		$ncolumn = 0;
		foreach($tableSchema->columns as $column) {
			$php_types[] = $column->phpType;
			$column_names[] = $column->name;
			$ret .= "\t\t" . strval($ncolumn ++) . " => '" . $column->name . "', // " . $column->phpType . "\n";
		}
		$ret .= "\t];\n\n";
		$sql = "SELECT * FROM {{{$table_name}}}";
		if ($where) {
			$sql .= " WHERE $where";
		}
		if ($this->count != 0) {
			$sql .= " LIMIT {$this->count}";
		}
		$raw_data = $this->db->createCommand($sql)->queryAll();
		foreach( $raw_data as $row ) {
			$ncolumn = 0;
			$txt_data .= "[";
			$first_column = true;
			foreach($row as $column) {
				if( $first_column) {
					$first_column = false;
				} else {
					$txt_data .= ", ";
				}
				$txt_data .= $this->getPhpValue($column, $php_types[$ncolumn]);
				$ncolumn++;
			}
			$txt_data .= "],\n";
		}
		$ret .= "\tpublic function run(\$db) {\n";
		$ret .= "\t\t\$rows_$table_name = [\n$txt_data\t\t];\n";
		$ret .= "\n";
		if( $this->truncateTables ) {
			$ret .= "\t\t\$db->createCommand()->checkIntegrity(false)->execute();\n";
			$ret .= "\t\t\$db->createCommand('DELETE FROM {{%" . $table_name . "}}')->execute();\n";
		}
		$ret .= <<<EOF
		echo "Seeding $table_name\\n";
		foreach( \$rows_$table_name as \$row ) {
			foreach( \$this->columns as \$ck => \$cv ) {
				if( \$cv == '' ) {
					unset(\$row[\$ck]);
					unset(\$this->columns[\$ck]);
				}
			}
			\$db->schema->insert("{{%$table_name}}", array_combine(\$this->columns, \$row));
		}
	}

} // class

EOF;
		return $ret;
	}

	protected function getPreamble($command, $schema, $table = null)
	{
		if ($table == null ) {
			$table = $schema;
		}
		$version = self::VERSION;
		switch( $this->format ) {
		case 'seeder':
		case 'fixture':
			$preamble = <<<PREAMBLE
<?php

/**
 * Churros v $version
 * ./yii churros/db/$command --format={$this->format} --fixturesPath={$this->fixturesPath}
 * Schema: $schema
 */

PREAMBLE;
			break;
		}
		return $preamble;
	}


} // class

