<?php

namespace santilin\churros\components;

use Yii;
use yii\db\IntegrityException;
use santilin\churros\helpers\AppHelper;
use santilin\churros\exceptions\ImportException;

/**
 * Esta es la clase base abstracta de las importaciones.
 * Contiene casi toda la funcionalidad. Las clases derivadas lo único que necesitan
 * definir es la estructura de los campos del csv y alguna función
 * específica para transformar algún campo de la importación.
 *
*
 * El proceso es el siguiente:
 *   1.- Se instancia una clase derivada de BaseImporter pasándole
 * el nombre del fichero a importar.
 *   2.- Se llama al método importa, con un parámetro $dry_run que indica si hay que grabar
 * los registros importados o sólamente hacer el proceso para ver los errors.
 *   3.- Al finalizar, tenemos los errors y los warnins en getErrors
 */
abstract class BaseImporter
{
	/** Códigos de error del proceso de importación */
	const OK = 0;
 	const FILE_ERROR = 1;
	const ABORTED_ON_ERROR = 2;
	const RECORD_WITH_ERRORS = 5;
	const EMPTY_RECORD = 3;
	const IGNORED_RECORD = 4;
	const IMPORTED_WITH_ERRORS = 5;

	protected $ignore_dups = false;
	protected $update_dups = false;
	protected $verbose = true;

    /**
     * @var string ruta y nombre del fichero a importar
     */
    protected $filename;

    /**
     * @var array the attributes of the currently imported record
     */
    protected $record_to_import = [];

    /**
     * @var a record
     */
    protected $record = null;

    /**
     * @var int el número de línea del fichero csv o el número de registro xml que se está leyendo
     */
	protected $csvline = 0;

    /**
     * @var int el número de registros importados
     */
	protected $imported = 0;

    /**
     * @var int el número de registros actualizados
     */
	protected $updated = 0;

    /**
     * @var array Los mensajes de error graves
     */
    protected $errors = [];

    /**
     * @var The base import model
     */
    protected $model;

    /**
     * @var Importacion La importación con la que estamos trabajando
     */
    protected $importacion = null;

    /**
     * @var boolean Si se para el proceso en el primer error
     */
    protected $abort_on_error = true;

    /**
     * var bool Si es una prueba o es una importación que grabará en la base de datos
     */
    protected $dry_run = true;

    public function __construct($options)
    {
		foreach( $options as $option => $value ) {
			$this->$option = $value;
		}
		$this->record = $this->createModel();
    }


	/**
	* Crea un registro para recoger los datos de cada linea y
	* establece los valores iniciales
	*
	* @return \App\Models\...
	*/
	abstract protected function createModel();

	/**
     * Devuelve el array con la información de cómo se importa cada campo del csv.
     *
     * @holadoc php/general Las funciones que se van a redefinir en una clase derivada no pueden ser static
     * @holadoc php/general Las funciones que se van a redefinir en una clase derivada y no se usan en la clase padre, deben declararse como abstract.
     *
     * @return array indexado por el nombre de la columna del csv: ejemplo:
     *               'Fecha pago' => [ 'getAñoMes', 'año_mes' ],
     *               COLUMNA_CSV => [ método import_getAñoMes, campo en la tabla importaciones, argumentos... ]
     */
    abstract protected function getImportFieldsInfo(): array;

    /**
     * Procesamiento de valores una vez leida una línea del csv y antes de importar el registro
     *
     * @param array $record los valores que ya están en el registro
     * @param array $csv_values Los valores a importar
     */
    protected function afterReadLine(array &$record, array $csv_values)
    {
    }

    protected function ignoreRecord(array $record):bool
    {
		return false;
    }

    protected function recordExists($record)
    {
		return null;
    }


    /**
		Lee los registros del fichero y los guarda en un array con los nombres de campos del modelo Importacion
		y con todas las transformaciones necesarias.
		Si detecta errores, los guarda en $this->errors;
     *
     * @param string $filename El fichero a importar
     * @param string $csvdelimiter
     * @param string $csvquote
     *
     * @return int el código de error definido como constante en esta clase
     */
    public function importCSV(string $filename, string $csvdelimiter = ",", string $csvquote = '"'): int
    {
        $this->filename = $filename;
        $this->errors = [];
		$transaction = Yii::$app->db->beginTransaction();
        $ret = $this->importCsvRecords($csvdelimiter, $csvquote);
		if( $ret == self::OK ) {
			$this->output("Insertados {$this->imported} registros");
			$this->output("Actualizados {$this->updated} registros");
			if (!$this->dry_run) {
				$transaction->commit();
			} else {
				$this->output("NO SE HAN GUARDADO LOS REGISTROS");
			}
		} else {
			$transaction->rollBack();
			switch( $ret ) {
			case self::ABORTED_ON_ERROR:
				$this->output("Aborted on error");
				break;
			case self::FILE_ERROR:
				break;
			case self::RECORD_WITH_ERRORS:
				break;
			default:
				throw new \Exception("Explain me: $ret");
			}
		}
		return $ret;
	}


    /**
     * Lee el fichero CSV e importa las líneas.
     * @param string $csvdelimiter
     * @param string $csvquote
     * @return bool si no ha habido errors graves
     */
    protected function importCsvRecords(string $csvdelimiter, string $csvquote): int
    {
        // @holadoc php/ficheros No hace falta comprobar si existe un fichero si luego lo vamos a abrir. fopen ya nos da el error si no existe.
        if (($file = @fopen($this->filename, 'r')) === false) {
            $this->errors['csv_open_file'] = error_get_last();
            return self::FILE_ERROR;
        }

        // Descartamos la linea de las cabeceras
        if (($csvline = fgetcsv($file, 0, $csvdelimiter, $csvquote)) === false) {
            $this->errors['csv_read_header'] = $this->filename . ": CSV file can not be read";
            return self::FILE_ERROR;
        }

        $import_fields_info = $this->getImportFieldsInfo();
        $csvheaders = array_keys($import_fields_info);
        if (count($csvline) !== count($csvheaders)) {
            $this->errors[] = "El número de columnas del fichero (" . count($csvline) . ") no coincide con el del importador (" . count($csvheaders). ")";
            return self::FILE_ERROR;
        }
        if (array_diff($csvline,$csvheaders) != []
        && array_diff($csvheaders,$csvline) != [] ) {
			foreach( $csvline as $key=>$value) {
				if( $csvline[$key] != $csvheaders[$key] ) {
					echo "$key=>$value <==> $key=>" . $csvheaders[$key]. "\n";
				}
			}
			// array_diff es case insensitive, usa array_udiff con strcasecmp si quieres que sea
			$this->errors[] = "El nombre de alguna(s) columna(s) del fichero csv no es correcto: " . print_r(array_udiff($csvline, $csvheaders, "strcasecmp"),true);
            return self::FILE_ERROR;
        }
        $csvheaders = $csvline; // Tomamos el orden del csv, no del input fields
        $this->csvline = 1;
        // Lee el fichero linea a linea y convierte a array la linea
        $ret = false;
        $import_fields_info = $this->getImportFieldsInfo();
        $has_errors = false;
        while (($csvline = fgetcsv($file, 0, $csvdelimiter, $csvquote)) !== false) {
			++$this->csvline;
			$this->output("Leyendo línea CSV {$this->csvline}");
			$ret = $this->importLine($import_fields_info, $csvheaders, $csvline);
			if( $ret != self::OK  && $ret != self::IGNORED_RECORD && $ret != self::EMPTY_RECORD ) {
				$has_errors = true;
				if( $this->abort_on_error ) {
					fclose($file);
					return self::ABORTED_ON_ERROR;
				}
			}
        }
        fclose($file);
        return ($has_errors ? self::IMPORTED_WITH_ERRORS : self::OK);
    }

    protected function importLine($import_fields_info, $csvheaders, $csvline): int
    {
		if ($csvline === null) {
			$this->add_error_get_last();
			return self::FILE_ERROR;
		} elseif ($csvline == []) {
			return self::EMPTY_RECORD;
			// Saltar la línea vacía (ver docs de php:fgetcsv)
		} elseif (count($csvline) !== count($csvheaders)) {
			$this->errors[] = "El número de columnas de la línea {$this->csvline} no es correcto";
			return self::FILE_ERROR;
		} else {
			// añadimos la cabecera al array
			$csvline = array_combine($csvheaders, $csvline);
			$this->record_to_import = [];
			$has_errors = false;
			foreach ($csvline as $csvindex => $csvvalue) {
				// Preparamos la llamada al método que va a importar este campo
				$fld_import_info = $import_fields_info[$csvindex];
				if ($fld_import_info != []) {
					$record_to_import_field = array_shift($fld_import_info);
					// Puede estar vacío (se ignora el campo), un campo o un array de campos
					if ($record_to_import_field != '') {
						$import_method = 'import_' . array_shift($fld_import_info);
						// Llamamos al método con los argumentos restantes
						try {
							// spat operator: ... pasa el array como parámetros indivuduales
							$import_value = $this->$import_method(trim($csvvalue), $csvline, ...$fld_import_info);
							if (!is_array($record_to_import_field)) {
								$record_to_import_field = [$record_to_import_field];
							}
							foreach ($record_to_import_field as $import_field) {
								$this->record_to_import[$import_field] = $import_value;
							}
						} catch (ImportException $e) {
							$has_errors = true;
							$this->addError($e->getMessage());
						}
					}
				}
			}
			if( !$this->ignoreRecord($this->record_to_import) ) {
				if( $has_errors ) {
					return self::RECORD_WITH_ERRORS;
				} else {
					// Guarda la línea original de este registro por si da error poder mostrar la línea del error
					$this->afterReadLine($this->record_to_import, $csvline);
					if( count($this->record_to_import) > 0 ) {
						return $this->importRecord($this->record_to_import);
					} else {
						return self::RECORD_WITH_ERRORS;
					}
				}
			} else {
				$this->output("Ignorando registro " . json_encode($this->record_to_import,JSON_UNESCAPED_UNICODE));
				return self::IGNORED_RECORD;
			}
		}
	}

	protected function importRecord(array $record): int
	{
		$has_error = $ignored = false;
		$r = $this->createModel();
		$r->setDefaultValues();
		// no valida duplicados para poder hacer update_dups
		if ($r->loadAll([ $r->formName() => $record ]) && $r->validate() ) {
			$model_dup = $this->modelExists($r);
			if ($model_dup) {
				if( $this->update_dups ) {
					// $r se va a insertar pero ya existe $model_dup, por lo tanto, actualizamos $model_dup
					$r = $model_dup;
					if (!$r->loadAll([ $r->formName() => $record ]) || !$r->saveAll(true) ) {
						$has_error = true;
					} else {
						$this->updated++;
						if( $this->verbose ) {
							$this->output("Actualizado registro " . $r->recordDesc());
						}
					}
				} else if ($this->ignore_dups) {
					$this->output("Ignorando registro duplicado " . $r->recordDesc());
					$ignored = true;
				} else {
					$this->addError("Registro duplicado " . $r->recordDesc());
					$has_error = true;
				}
			} elseif (!$r->saveAll(false)) {
				if( $r->getFirstError('yii\db\IntegrityException')) {
					if ($this->ignore_dups) {
						$this->output("Ignorando registro duplicado " . $r->recordDesc());
						$ignored = true;
					} else {
						$this->addError("Registro duplicado " . $r->recordDesc());
						$has_error = true;
					}
				} else {
					$has_error = true;
				}
			} else {
				$this->imported++;
				if( $this->verbose ) {
					$this->output("Importado registro " . $r->recordDesc());
				}
			}
		} else {
			$has_error = true;
		}
		if ($has_error) {
			$this->addError($r->getOneError() . json_encode($r->getAttributes(),JSON_UNESCAPED_UNICODE) );
			if ($this->abort_on_error) {
				return self::ABORTED_ON_ERROR;
			}
		}
		return $has_error ? self::RECORD_WITH_ERRORS : self::OK;
    }


    /**
     * Rellena los atributos de un registro a partir de los valores en el array $record.
     *
     * @param HolaModel $record
     * @param array $values Los valores a importar
     */
    public function loadAll(& $record, $values)
    {
        foreach ($values as $field => $value) {
			if (is_array($value)) {
				continue;
			}
			if( $value != null ) { // keep default values
				$record->$field = $value;
			}
        }
        foreach ($values as $field => $value) {
			if( !is_array($value) ) {
				continue;
			}
			$record->loadAll([ $record->formName() => [ $field => $value ]], [$field]);
		}
        return $record->validate();
    }


	/**
		Lee los registros del fichero y los guarda en un array con los nombres de campos del modelo Importacion
		y con todas las transformaciones necesarias.
		Si detecta errores, los guarda en $this->errors;
     *
     * @param string $filename El fichero a importar
     * @param string $csvdelimiter
     * @param string $csvquote
     *
     * @return int el código de error definido como constante en esta clase
     */
    public function importXML(string $filename, string $csvdelimiter = ",", string $csvquote = '"'): int
    {
        $this->filename = $filename;
        $this->errors = [];
        return $this->importXMLRecords($csvdelimiter, $csvquote);
	}

    /**
     * Lee el fichero XML e importa los registros.
     * @return bool TRUE si no ha habido errors graves
     */
	protected function importXMLRecords()
    {
        libxml_use_internal_errors(); // no mostrar errors, guardarlos
        $records = simplexml_load_file($this->filename);

        $this->csvline = 0;
        // Obtenemos el atributo del nodo raíz del XML
		$csvlines = $this->xmlRecordToCSV($records->children());
		$import_fields_info = $this->getImportFieldsInfo();
		$csvheaders = array_keys($import_fields_info);
		foreach($csvlines as $csvline) {
			$ret = $this->importLine($import_fields_info, $csvheaders, $csvline);
			if( !$ret ) {
				break;
			}
		}
		return $ret;
    }

    /**
     * dumps a CSV template with the fields defined in the derived importer
     */
    public function genCSVTemplate($echo = true)
    {
        $import_fields_info = $this->getImportFieldsInfo();
        $ret = '';
        foreach( $import_fields_info as $key => $value ) {
			if( $ret != '' ) {
				$ret .= ",";
			}
			$ret .= "\"$key\"";
        }
        if( $echo ) {
			echo "$ret\n";
		} else {
			return $ret;
		}
    }


    // FUNCIONES ESPECÍFICAS PARA LA IMPORTACIÓN DE UN CAMPO

    /**
     * Ignora este campo en la importación.
     *
     * @param type $csv_value
     * @param type $array_csv
     */
    protected function import_ignore($csv_value, $array_csv) {
        return;
    }

    /**
     * Importa el campo con un valor constante
     *
     * @param mixed $csv_value
     * @param array $array_csv
     * @param mixed $value
     * @return mixed
     */
    protected function import_constant($csv_value, array $array_csv, $value)
    {
        return $value;
    }

    protected function import_copy($csv_value, array $array_csv): string
    {
        return trim($csv_value);
    }

    protected function import_copy_other($csv_value, $array_csv, $other): string
    {
        return trim($array_csv[$other]);
    }

    protected function import_copy_with_default($csv_value, $array_csv, string $default): string
    {
		if( trim($csv_value) == '') {
			return $default;
		} else {
			return trim($csv_value);
        }
    }

    /**
     * Copia solo los primeros len caracteres del campo a importar
     */
    protected function import_copy_len($csv_value, $array_csv, $len) {
		return substr(trim($csv_value), 0, $len);
    }

    /**
     * Copia convirtiendo de latin1 a utf8
     */
    protected function import_copy_latin1($csv_value, $array_csv) {
		return iconv('latin1','utf8',trim($csv_value));
    }

    /**
     * Copia concatenando el valor a un valor ya importado
     */
    protected function import_concat($csv_value, $array_csv, $csv_first, $separator = '') {
        return $this->record_to_import[$csv_first] . $separator . $csv_value;
    }

    protected function import_copy_date($date, $array_csv) {
        // El formato en TIB es dd/mm/yyyy
        $date_parts = strptime($date, '%d/%m/%Y');
        if ($date_parts === false) {
			$date_parts = strptime($date, "%Y-%m-%dT%H:%M:%S");
		}
		if ($date_parts !== false) {
            return sprintf("%04d-%02d-%02d", 1900 + intval($date_parts['tm_year']), intval($date_parts['tm_mon']) + 1, intval($date_parts['tm_mday']));
        } else {
            $this->addError("Fecha '$date' errónea");
        }
    }

    protected function import_float($float, array $array_csv): string
    {
        return floatval(str_replace(",",".",$float));
    }

    protected function import_percent($percent, array $array_csv): string
    {
        return $this->import_float($percent, $array_csv);
    }

    protected function import_euros($euros, array $array_csv): string
    {
        return $this->import_float($euros, $array_csv);
    }

    protected function import_find_related_id($related_value, $array_csv,
		$related_model, $related_fields, $related_field)
    {
		$related_class = "\app\models\\$related_model";
		$related_record = new $related_class;
		if( !is_array($related_fields) ) {
			$related_fields = [ $related_fields ];
		}
		foreach( $related_fields as $search_field ) {
			$found = $related_record->find()->andWhere( [ $search_field => $related_value] )->one();
			if( $found ) {
				return $found->$related_field;
			}
		}
		throw new ImportException("$related_value not found in $related_model" . "[". implode($related_fields, ','). "]");
    }

    protected function import_find_in_array($value, $array_csv, $array)
    {
		foreach($array as $arr_key=>$arr_value) {
			if( $value == $arr_value ) {
				return $arr_key;
			}
		}
		return null;
    }


	/**
 	 * Añade un error genérico a esta importación.
	 * @param string $message
	 */
	public function addError(string $message)
	{
		if( $this->csvline != 0 ) {
			$exc_message = "l:{$this->csvline}: $message";
		} else {
			$exc_message = $message;
		}
		$this->errors[] = $exc_message;
	}

	protected function add_error_get_last()
	{
		$last_error = error_get_last();
		if( $last_error ) {
			$this->errors[] = $last_error['message'];
		}
	}

	protected function output(string $message)
	{
		if( !$this->verbose ) {
			return;
		}
		if ($this->dry_run) {
			echo "dry_run: ";
		}
		echo $message;
		echo "\n";
	}

    /**
     * prints errors to the console if any
     */
    public function showErrors($result)
    {
		if( $result != self::OK ) {
			foreach( $this->getErrors() as $key => $error ) {
				if ( is_array($error) ) {
					$strerror = array_pop($error);
				} else {
					$strerror = & $error;
				}
				echo "$strerror\n";
			}
		}
	}

    public function getErrors(): array
    {
        return $this->errors;
    }

	/**
	 * Obtiene todos los errors en una sola cadena de texto
	 */
    public function getErrorsAsString()
    {
		$ret = "";
		foreach( $this->getErrors() as $error ) {
			if (strlen($ret) != 0 ) {
				$ret .= ".";
			}
			if ( is_array($error) ) {
				$ret .= array_pop($error);
			} else {
				$ret .= $error;
			}
		}
		return $ret;
	}


}
