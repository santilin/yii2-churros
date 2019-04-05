<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace santilin\Churros\commands;
use Yii;
use yii\di\Instance;
use yii\db\Connection;
use yii\console\Controller;

/**
 * Churros dump and seed commands
 *
 *
 * @author Santilín <santi@noviolento.es>
 * @since 1.0
 */
class DumpSeedController extends Controller
{
	/** The version of this command */
	const VERSION = '0.1';

	/**
     * @var Connection|array|string the DB connection object or the application component ID of the DB connection to use
     * when applying migrations. Starting from version 2.0.3, this can also be a configuration array
     * for creating the object.
     */
    public $db = 'db';

	/** @var bool Wether to add the DROP TABLE command */
	public $truncateTables = true;

	/** @var bool wether to create a file in seedersPath */
	public $createFile = true;

	/** @var string path to the seeders directory, defaults to @app/database/seeders */
	public $seedersPath = "@app/database/seeders";


    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['truncateTables','createFile','seedersPath']
        );
    }

    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            't' => 'truncateTables',
            'c' => 'createFile',
            'p' => 'seedersPath'
        ]);
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * It checks the existence of the [[migrationPath]].
     * @param \yii\base\Action $action the action to be executed.
     * @return bool whether the action should continue to be executed.
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
			$this->db = Instance::ensure($this->db, Connection::className());
            return true;
        }
        return false;
    }

    protected function getPhpValue($value, $phptype)
    {
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
				if( $value == null ) {
					return "null";
				} else {
					return "'" . str_replace("'", "\\'", $value). "'";
				}
			default:
				throw new \Exception( "Type $phptype not supported in PosyakeController::getPhpValue" );
		}
    }

	/**
     * Dumps a full schema
     *
     * @param string $schemaName the schema name (optional)
     */
    public function actionDumpSchema($schemaName = '')
    {
		$full_dump = "<?php\n"
			. "/**\n"
			. " * Posyake v" . strval(self::VERSION) . "\n"
			. " * ./yii posyake/dump-schema " . ( $schemaName != '' ?:  $this->db->dsn ) . "\n"
			. " * Timestamp: " . date('Y-m-d H:i:s', time() ) . "\n"
			. " * \n"
			. " */\n";
		$runseeder = '';
		$tables = $this->db->schema->getTableSchemas($schemaName, true);
		foreach ($tables as $table) {
			if( $table->name != 'migration' ) {
				echo "Dumping {$table->name}\n";
				$full_dump .= $this->dumpTable($table, $schemaName);
				$runseeder .= "\t\t\$s = new {$table->name}Seeder(); \$s->run(\$db);\n";
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
	}

	/**
     * Dumps a table from a schema
     *
     * @param string $tableName the table to be dumped
     * @param string $schemaName the schema the table belongs to
     */
    public function actionDumpTable($tableName, $schemaName = '')
    {
		if( $schemaName != '') {
			$tableName = "$schemaName.$tableName";
		}
		$tableSchema = $this->db->schema->getTableSchema($tableName, true /*refresh*/);
		if ($tableSchema == null) {
			throw new \Exception("$tableName not found in schema $schemaName");
		}
		$preamble = "<?php\n\n/**\n"
			. " * Posyake v" . $this->version . "\n"
			. " * ./yii posyake/dump-table $tableName of schema  " . ( $schemaName == '' ?:  $this->db->dsn ) . "\n"
			. " * Timestamp: " . date('Y-m-d H:M:S', time() ) . "\n"
			. " * \n"
			. " */";
		if ($this->createFile ) {
			$write_file = true;
			$filename = Yii::getAlias($this->seedersPath) . "/{$tableName}Seeder.php";
			if (\file_exists($filename) && !$this->confirm("The file $filename already exists. Do you want to overwrite it?") ) {
				$write_file = false;
			}
			if ($write_file) {
				\file_put_contents($filename, $preamble . $this->dumpTable($tableSchema));
				echo "Created seeder for table $tableName in $filename\n";
			}
		} else {
			echo $preamble . $this->dumpTable($tableSchema);
		}
	}

	/**
	 * Seeds the specified table from the specified file or a default one [tablenameSeeder]
	 */
	public function actionSeedTable($tableName, $seedfilename = null )
	{
		if( $seedfilename == null ) {
			$seedfilename = Yii::getAlias($this->seedersPath) . "/{$tableName}Seeder.php";
		}
		require_once($seedfilename);
		$classname = "{$tableName}Seeder";
		$class = new $classname;
		$class->run($this->db);
	}

	/**
	 * Seeds the current schema with the specified file
	 */
	public function actionSeedSchema($seedfilename = null )
	{
		if( $seedfilename == null ) {
			$seedfilename = Yii::getAlias($this->seedersPath) . "/SchemaSeeder.php";
		}
		require_once($seedfilename);
		$s = new \SchemaSeeder;
		echo "Seeding schema from $seedfilename\n";
		$s->run($this->db);
	}

	protected function dumpTable($tableSchema)
    {
		$txt_data = '';
		$insert_sql = '';
		$php_types = [];
		$columna_names = [];
		$insert_sql = '';
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
		$raw_data = $this->db->createCommand("SELECT * FROM $table_name")->queryAll();
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
			$ret .= "\t\t\$db->createCommand(\"DELETE FROM $table_name\")->execute();\n";
		}
		$ret .= "\t\techo \"Seeding $table_name\\n\";\n";
		$ret .= "\t\tforeach( \$rows_$table_name as \$row ) {\n";
		$ret .= "\t\t\t\$db->schema->insert('$table_name', array_combine(\$this->columns, \$row));\n";
		$ret .= "\t\t}\n";
		$ret .= "\t}\n";
		$ret .= "\n";
		$ret .= "} // class \n";
		$ret .= "\n";
		return $ret;
	}

} // class

