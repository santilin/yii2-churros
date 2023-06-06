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
    public $format = 'seeder';

	/** @var bool Wether to add the DROP TABLE command */
	public $truncateTables = true;

	/** @var bool wether to create a file in seedersPath */
	public $createFile = true;

	/** @var string path to the seeders directory, defaults to @app/database/seeders */
	public $seedersPath = "@app/database/seeders";

	/** @var string path to the fixtures directory, defaults to @app/tests/fixtures/data*/
	public $fixturesPath = "@app/database/fixtures";

    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['db', 'format', 'truncateTables','createFile','seedersPath','fixturesPath']
        );
    }

    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'f' => 'format',
            't' => 'truncateTables',
            'c' => 'createFile',
            'p' => 'seedersPath',
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
                throw new InvalidConfigException("{$this->format}: formato desconocido. Elige uno de " . join(self::$formats,","));
			}
			$this->db = Instance::ensure($this->db, Connection::className());
            return true;
        }
        return false;
    }

    protected function getPhpValue($value, $phptype)
    {
		if (substr($phptype,0,3) == 'int' || substr($phptype,0,7) == 'tinyint') {
			$phptype = 'integer';
		} else if (substr($phptype,0,7) == 'decimal') {
			$phptype = 'double';
		} else if (substr($phptype,0,7) == 'varchar') {
			$phptype = 'string';
		}
		switch($phptype) {
			case "integer":
			case "float":
			case "double":
			case "bool":
			case "boolean":
				if ( $value == null ) {
					return "null";
				} else {
					return $value;
				}
			case "string":
			case 'timestamp':
			case 'date':
			case "mediumtext":
			case 'mediumblob':
			case 'text':
				if( $value == null ) {
					return "null";
				} else {
					return "'" . strtr($value, ["\\'" => "\\\\", "'" => "\\'"]) . "'";
				}
			default:
				throw new \Exception( "Type $phptype not supported in Churros/DbController::getPhpValue" );
		}
    }

	/**
     * Dumps a full schema
     *
     * @param string $schemaName the schema name (optional)
     */
    public function actionDumpSchema(array $tables = [], string $schemaName = '')
    {
		if( $this->format != 'seeder' && $this->format != 'fixture' ) {
			throw new InvalidConfigException("{$this->format}: formato no contemplado. Sólo se contempla 'seeder' y 'fixture'");
		}
		if ($this->format == 'seeder') {
			if (($preamble_schema = $schemaName) == '') {
				$preamble_schema = $this->db->dsn;
			}
			$preamble = $this->getPreamble('dump-schema', $preamble_schema,
				count($tables) ? implode(',', $tables) : 'all-tables');
			$runseeder = '';
			$schema_tables = $this->db->schema->getTableSchemas($schemaName, true);
			$full_dump = $preamble;
			foreach ($schema_tables as $table) {
				if (!count($tables) || (count($tables) && in_array($table->name, $tables))) {
					if( $table->name != 'migration' ) {
						echo "Dumping {$table->name}\n";
						$full_dump .= $this->dumpTable($table, $schemaName);
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
					$table_dump .= $this->dumpTable($table, $schemaName);
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
						echo "Created seeder for schema in $filename\n";
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
     * @param string $schemaName the schema the table belongs to
     */
    public function actionDumpTable(string $tableName, string $where = null, string $schemaName = null)
    {
		if( $schemaName) {
			$tableName = "$schemaName.$tableName";
		} else {
			$schemaName = $this->db->dsn; // only for preamble
		}
		$tableSchema = $this->db->schema->getTableSchema($tableName, true /*refresh*/);
		if ($tableSchema == null) {
			throw new \Exception("$tableName not found in schema $schemaName");
		}
		$preamble = $this->getPreamble('dump-table', $tableName, $schemaName);
		if ($this->createFile ) {
			$write_file = true;
			if ($this->format == 'seeder') {
				$filename = Yii::getAlias($this->seedersPath) . "/{$tableName}Seeder.php";
			} else {
				$filename = Yii::getAlias($this->fixturesPath) . "/{$tableName}.php";
			}
			if (\file_exists($filename) && !$this->confirm("The file $filename already exists. Do you want to overwrite it?") ) {
				$write_file = false;
			}
			if ($write_file) {
				\file_put_contents($filename, $preamble . $this->dumpTable($tableSchema, $where));
				echo "Created seeder for table $tableName in $filename\n";
			}
		} else {
			echo $preamble . $this->dumpTable($tableSchema, $where);
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

	protected function dumpTable($tableSchema, string $where = null)
	{
		switch( $this->format ) {
		case 'seeder':
			return $this->dumpTableAsSeeder($tableSchema, $where);
		case 'fixture':
			return $this->dumpTableAsFixture($tableSchema, $where);
		default:
			throw new InvalidArgumentException("dump-table: $this->format: no contemplado");
		}
	}

	protected function dumpTableAsFixture($tableSchema, string $where = null): string
    {
		$txt_data = "return [\n";
		$php_types = [];
		$columna_names = [];
		$table_name = $tableSchema->name;

		foreach($tableSchema->columns as $column) {
			$php_types[] = $column->dbType;
			$column_names[] = $column->name;
		}
		if ($where) {
			$where = " WHERE $where";
		}
		$raw_data = $this->db->createCommand('SELECT * FROM {{' . $table_name. "}} $where")->queryAll();
		$nrow = 0;
		foreach( $raw_data as $row ) {
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


	protected function dumpTableAsSeeder($tableSchema, $where = null): string
    {
		$txt_data = '';
		$php_types = [];
		$columna_names = [];
		$table_name = $tableSchema->name;

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
		if ($where) {
			$where = " WHERE $where";
		}
		$raw_data = $this->db->createCommand('SELECT * FROM {{' . $table_name. "}} $where")->queryAll();
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
		$timestamp = date('Y-m-d H:i:s', time());
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
 * ./yii churros/db/$command --format {$this->format} $table $schema
 * Schema: $schema
 * Timestamp: $timestamp
 */

PREAMBLE;
			break;
		}
		return $preamble;
	}


} // class

