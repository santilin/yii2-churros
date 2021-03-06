<?php

namespace santilin\churros\components;

use santilin\churros\helpers\AppHelper;
use santilin\churros\components\ImportException;

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
abstract class BaseImporter {

	/** Códigos de error del proceso de importación */
	const OK = 0;
	const RECORD_ERRORS = 2;
	const IMPORTED_WITH_ERRORS = 3;
	const CSV_FILE_ERROR = 4;

    /**
     * @var string ruta y nombre del fichero a importar
     */
    protected $filename;

    /**
     * @var array los registros leídos del CSV que se importarán finalmente
     */
    protected $records_to_import = [];

    /**
     * @var array the attributes of the currently imported record
     */
    protected $record_to_import = [];

    /**
     * @var a record
     */
    protected $record = null;

    /**
     * @var integer el número de línea del fichero csv o el número de registro xml que se está leyendo
     */
     protected $csvline = 0;

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
		$this->record = $this->createRecord();
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

    public function getErrors() {
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


	/**
	* Crea un registro para recoger los datos de cada linea y
	* establece los valores iniciales
	*
	* @return \App\Models\...
	*/
	abstract protected function createRecord();

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
    public function import($filename, $csvdelimiter = ",", $csvquote = '"')
    {
        $this->filename = $filename;
        $this->errors = [];
        if (!$this->readRecords($csvdelimiter, $csvquote)) {
			$this->add_error_get_last();
            return self::CSV_FILE_ERROR;
        }
        if (!$this->dry_run) {
			$transaction = $this->record->getDb()->beginTransaction();
        }
        $has_errors = false;
        foreach ($this->records_to_import as $record) {
			$this->csvline = $record['csvline']; // Para recuperar el número de línea del registro erróneo
//  			echo "Leyendo registro de la línea {$this->csvline}\n";
            $r = $this->createRecord();
            $r->setDefaultValues();
            if ($this->validateRecord($r, $record)) {
                if (!$this->dry_run) {
					try {
						if (!$r->save() ) {
							$this->addError($r->getErrorsAsString() );
							$has_errors = true;
						} else {
							echo "Importado registro " . $r->recordDesc() . "\n";
						}
					} catch( \App\Exceptions\InternalException $e ) {
						$this->addError($e->getMessage());
						$has_errors = true;
					}
                }
            } else {
				$this->addError( $r->getOneError() . json_encode($r) );
				$has_errors = true;
            }
        }
        if ($has_errors) {
			if( $this->abort_on_error) {
				if (!$this->dry_run) {
					$transaction->rollBack();
				}
				return self::RECORD_ERRORS;
			}
		}
		if (!$this->dry_run) {
			$transaction->commit();
		}
		return ($has_errors) ? self::IMPORTED_WITH_ERRORS : self::OK;
    }

	/**
	* Añade un error genérico a esta importación.
	* @param string $message
	*/
	public function addError($message)
	{
		if( $this->csvline != 0 ) {
			$exc_message = "l:{$this->csvline}: $message";
		} else {
			$exc_message = $message;
		}
		$this->errors[] = $exc_message;
		if ($this->abort_on_error) {
			throw new ImportException($exc_message);
		}
	}

	protected function add_error_get_last()
	{
		$last_error = error_get_last();
		$this->errors[] = $last_error['message'];
	}

    /**
     * Procesamiento de valores una vez leida una línea del csv y antes de importar el registro
     *
     * @param array $record los valores que ya están en el registro
     * @param array $csv_values Los valores a importar
     */
    protected function afterReadLine(& $record, $csv_values)
    {
    }

    /**
     * Importa un registro a partir de los valores en el array $record.
     *
     * @param HolaModel $record
     * @param array $values Los valores a importar
     */
    public function validateRecord(& $record, $values)
    {
        foreach ($values as $field => $value) {
			if ($field == 'csvline') {
				continue;
			}
			if( $value != null ) { // keep default values
				$record->$field = $value;
			}
/*
			if( $record->hasAttribute($field) ) {
			} else {
				$this->addError("El campo $field no existe en el registro de " . get_class($record));
			}
*/
        }
        return $record->validate();
    }


    /**
     * Lee el fichero CSV o XML y rellena la variable $records_to_import.
     * @param string $csvdelimiter
     * @param string $csvquote
     * @return bool TRUE si no ha habido errors graves
     */
    protected function readRecords($csvdelimiter, $csvquote)
    {
		if( AppHelper::endsWith($this->filename, ".xml") ) {
			return $this->readRecordsFromXml();
		} else {
			return $this->readRecordsFromCSV($csvdelimiter, $csvquote);
		}
	}


    /**
     * Lee el fichero CSV y rellena la variable $records_to_import.
     * @param string $csvdelimiter
     * @param string $csvquote
     * @return bool si no ha habido errors graves
     */
    protected function readRecordsFromCSV($csvdelimiter, $csvquote)
    {
        // @holadoc php/ficheros No hace falta comprobar si existe un fichero si luego lo vamos a abrir. fopen ya nos da el error si no existe.
        if (($file = @fopen($this->filename, 'r')) === false) {
            $this->errors['csv_open_file'] = error_get_last();
            return false;
        }
        $this->records_to_import = [];

        // Descartamos la linea de las cabeceras
        if (($csvline = fgetcsv($file, 0, $csvdelimiter, $csvquote)) === false) {
            $this->errors['csv_read_header'] = $this->filename . ": CSV file can not be read";
            return false;
        }

        $import_fields_info = $this->getImportFieldsInfo();
        $csvheaders = array_keys($import_fields_info);
        if (count($csvline) !== count($csvheaders)) {
            $this->errors[] = "El número de columnas del fichero (" . count($csvline) . ") no coincide con el del importador (" . count($csvheaders). ")";
            return false;
        }
        if (array_diff($csvline,$csvheaders) != []
        && array_diff($csvheaders,$csvline) != [] ) {
			foreach( $csvline as $key=>$value) {
				if( $csvline[$key] != $csvheaders[$key] ) {
					echo "$key=>$value <==> $key=>" . $csvheaders[$key]. "\n";
				}
			}
			// @holadoc php/general array_diff es case insensitive, usa array_udiff con strcasecmp si quieres que sea
			$this->errors[] = "El nombre de alguna(s) columna(s) del fichero csv no es correcto: " . print_r(array_udiff($csvline, $csvheaders, "strcasecmp"),true);
            return false;
        }
        $this->csvline = 1;
        // Lee el fichero linea a linea y convierte a array la linea
        $ret = false;
        $import_fields_info = $this->getImportFieldsInfo();
        while (($csvline = fgetcsv($file, 0, $csvdelimiter, $csvquote)) !== false) {
			$ret = $this->readCsvLine($import_fields_info, $csvheaders, $csvline);
			if( !$ret ) {
				break;
			}
        }
        fclose($file);
        return $ret;
    }


    protected function readCsvLine($import_fields_info, $csvheaders, $csvline)
    {
		++$this->csvline;
		// @holadoc php/general Siempre comprobar los errores en cualquier función de php: fgetcsv
		if ($csvline === null) {
			$this->add_error_get_last();
		} elseif ($csvline == []) {
			// Saltar la línea vacía (ver docs de php:fgetcsv)
		} elseif (count($csvline) !== count($csvheaders)) {
			$this->errors[] = "El número de columnas de la línea $this->csvline no es correcto";
			return false;
		} else {
			// añadimos la cabecera al array
			$csvline = array_combine($csvheaders, $csvline);
			$this->record_to_import = [];
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
							$this->addError($e->getMessage());
						}
					}
				}
			}
			// Guarda la línea original de este registro por si da error poder mostrar la línea del error
			$this->record_to_import['csvline'] = $this->csvline;
			$this->afterReadLine($this->record_to_import, $csvline);
			if( count($this->record_to_import) > 0 ) {
				if ( isset($this->record_to_import[0]) ) {
					foreach( $this->record_to_import as $rti ) {
						$this->records_to_import[] = $rti;
					}
				} else {
					$this->records_to_import[] = $this->record_to_import;
				}
			}
		}
		return true;
	}

    /**
     * Lee el fichero XML y rellena la variable $records_to_import.
     * @return bool TRUE si no ha habido errors graves
     */
    protected function readRecordsFromXml()
    {
        libxml_use_internal_errors(); // no mostrar errors, guardarlos
        $records = simplexml_load_file($this->filename);

        $this->csvline = 0;
        $this->records_to_import = [];
        // Obtenemos el atributo del nodo raíz del XML
		$csvlines = $this->xmlRecordToCSV($records->children());
		$import_fields_info = $this->getImportFieldsInfo();
		$csvheaders = array_keys($import_fields_info);
		foreach($csvlines as $csvline) {
			$ret = $this->readCsvLine($import_fields_info, $csvheaders, $csvline);
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
    protected function import_constant($csv_value, $array_csv, $value) {
        return $value;
    }

    protected function import_copy($csv_value, $array_csv) {
        return trim($csv_value);
    }

    protected function import_copy_other($csv_value, $array_csv, $other) {
        return trim($array_csv[$other]);
    }

    protected function import_copy_with_default($csv_value, $array_csv, $default) {
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

    protected function import_float($float, $array_csv) {
        return floatval(str_replace(",",".",$float));
    }

    protected function import_porcentaje($porcentaje, $array_csv) {
        return $this->import_float($porcentaje, $array_csv);
    }

    protected function import_euros($euros, $array_csv) {
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
     * Devuelve el array con la información de cómo se importa cada campo del csv.
     *
     * @holadoc php/general Las funciones que se van a redefinir en una clase derivada no pueden ser static
     * @holadoc php/general Las funciones que se van a redefinir en una clase derivada y no se usan en la clase padre, deben declararse como abstract.
     *
     * @return array indexado por el nombre de la columna del csv: ejemplo:
     *               'Fecha pago' => [ 'getAñoMes', 'año_mes' ],
     *               COLUMNA_CSV => [ método import_getAñoMes, campo en la tabla importaciones, argumentos... ]
     */
    abstract protected function getImportFieldsInfo();
}
