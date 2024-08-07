<?php

namespace santilin\churros;

use Yii;
use yii\db\ActiveRecord;
use yii\base\{InvalidConfigException,InvalidArgumentException};
use yii\helpers\ArrayHelper;
use santilin\churros\helpers\{YADTC,AppHelper,FormHelper};
use santilin\churros\ModelSearchTrait;
use santilin\churros\models\{ModelSuccessesTrait,ModelWarningsTrait};

trait ModelInfoTrait
{
	use ModelSuccessesTrait, ModelWarningsTrait;
    protected $crudScenarios = [ 'default', 'create', 'duplicate', 'update' ];

	public function getCrudScenarios(): array
	{
		// crudScenarios is protected to avoid returning it in $model->attributes()
		return $this->crudScenarios;
	}
	// For validators only
    static public function empty($value)
    {
		return empty($value);
	}

	static public function bareTableName()
	{
		return strtr( self::tableName(), [ '{' => '', '}' => '', '%' => '' ] );
	}

	public function t($category, $message, $params = [], $language = null )
	{
		$t_params = array_filter($params, function($value) { return is_string($value); });
		$translated = Yii::t($category, $message, $t_params, $language);
		if( ($language == 'es' || substr(Yii::$app->language,0,2) == 'es') ) {
			$male_words = AppHelper::SPANISH_MALE_WORDS;
		} else {
			$male_words = [];
		}
		$matches = $placeholders = [];
		$female = $this->getModelInfo('female');
		if( preg_match_all('/({([a-zA-Z0-9\._]+)})+/', $translated, $matches) ) {
			foreach( $matches[2] as $match ) {
				$bracket_match = '{'.$match.'}';
				if( substr($match,0,6) == 'model.' ) {
					$fld = substr($match, 6);
					$placeholders[$bracket_match] = ArrayHelper::getValue($this,$fld,'');
				} else switch( $match ) {
				case 'title':
					$placeholders[$bracket_match] = lcfirst(static::getModelInfo('title'));
					break;
				case 'title_plural':
					$placeholders[$bracket_match] = lcfirst(static::getModelInfo('title_plural'));
					break;
				case 'Title':
					$placeholders[$bracket_match] = ucfirst(static::getModelInfo('title'));
					break;
				case 'Title_plural':
					$placeholders[$bracket_match] = ucfirst(static::getModelInfo('title_plural'));
					break;
				case 'record':
					$placeholders[$bracket_match] = $this->recordDesc();
					break;
				case 'record_link':
					$placeholders[$bracket_match] = $this->recordDesc('link');
					break;
				case 'record_long':
					$placeholders[$bracket_match] = $this->recordDesc('long');
					break;
				case 'record_medium':
					$placeholders[$bracket_match] = $this->recordDesc('medium');
					break;
				case 'record_short':
					$placeholders[$bracket_match] = $this->recordDesc('short');
					break;
				default:
					if( isset($male_words[$match]) ) {
						if( $female )  {
							$placeholders[$bracket_match] = $match;
						} else {
							$placeholders[$bracket_match] = $male_words[$match];
						}
					} else { // allows any other array inside brackets
						$value = ArrayHelper::getValue($params, $match, 'qué raro');
						if ($value !== 'qué raro') {
							$placeholders[$bracket_match] = $value;
						}
					}
				}
			}
		}
		return strtr($translated, $placeholders);
	}

	public function recordDesc(string $format=null, int $max_len = 0): string
	{
		$ret = '';
		if( $format == null || $format == 'short' ) {
			$_format = self::getModelInfo('record_desc_format_short');
		} elseif ($format == 'medium') {
			$_format = self::getModelInfo('record_desc_format_medium');
		} elseif ($format == 'long') {
			$_format = self::getModelInfo('record_desc_format_long');
		} elseif ($format == 'desc') {
			$fields = static::findDescFields();
			$_format = '{' . implode('}, {',array_filter($fields)) . '}';
		} elseif ($format == 'code&desc') {
			$fields = static::findCodeAndDescFields();
			$_format = '{' . implode('}, {',array_filter($fields)) . '}';
		} else {
			$_format = $format;
		}
		$values = $matches = [];
		if( preg_match_all('/{([a-zA-Z0-9\._]+)(\%([^}])*)*}+/', $_format, $matches) ) {
			foreach( $matches[0] as $n => $match ) {
				$value = ArrayHelper::getValue($this, $matches[1][$n]);
				if( is_object($value) && method_exists($value, 'recordDesc') ) {
					$value = $value->recordDesc($format, $max_len);
				}
				$sprintf_part = $matches[2][$n];
				if( $sprintf_part == '' ) {
					$sprintf_part = "%s";
				} else if( $sprintf_part == '%T' ) {
					$sprintf_part = '%s';
					$value = Yii::$app->formatter->asDateTime($value);
				} else if( $sprintf_part == '%D' ) {
					$sprintf_part = '%s';
					$value = Yii::$app->formatter->asDate($value);
				} else if ($sprintf_part == '%_a') { // allowed_values
					$sprintf_part = '%s';
					$fname = $matches[1][$n];
					$getter = "get" . AppHelper::modelize($fname) . "Label";
					$value = call_user_func([$this, $getter]);
				}
				$_format = str_replace($match, $sprintf_part, $_format);
				$values[] = $value;

			}
			$ret = sprintf($_format, ...$values);
		} else {
			$ret = $_format;
		}
		if( $max_len == 0 ) {
			return $ret;
		} else if ($max_len < 0 ) {
			return substr($ret, 0, -$max_len);
		} else {
			$len = strlen($ret);
			if( $len > $max_len ) {
				$ret = mb_substr($ret, 0, floor($max_len/2)-2) . '...' . mb_substr($ret, -floor($max_len/2)+2);
			}
		}
		return $ret;
	}

	public function relatedRecordDescs(array $relations, string $format=null, int $max_len = 0): array
	{
		return [
			$this->getPrimaryKey() => [
				$this->recordDesc($format, $max_len),
				$this->recRelatedRecordDescs($relations, $this, 0, $format, $max_len)
			]
		];
	}

	public function recRelatedRecordDescs(array $relations, $model, int $level, string $format=null, int $max_len = 0): array
	{
		$ret = [];
		if ($level < count($relations)) {
			$relation = $relations[$level++];
			$relrecs = $model->$relation;
			if ($relrecs != null) {
				foreach ( $relrecs as $related_record) {
					$ret[$related_record->getPrimaryKey()] = [
						$related_record->recordDesc($format, $max_len),
						$related_record->recRelatedRecordDescs($relations, $related_record, $level, $format, $max_len)
					];
				}
			}
		}
		return $ret;
	}

	public function linkToMe(string $format = 'long', string $action = 'view', string $base_route = null): string
	{
		if ($base_route == null) {
			$base_route = Yii::$app->module?->id??'';
		}
		$link = self::getModelInfo('controller_name') . "/$action";
		return \yii\helpers\Html::a($this->recordDesc($format, 0),
				array_merge([$link], $this->getPrimaryKey(true)));
	}

	public function linkTo($action, $prefix = '', $format = 'short', $max_len = 0)
	{
		$url = $prefix;
		if ($url != '') {
			$url .= "/";
		}
		$url .= $this->controllerName();
		if ($this->getIsNewRecord()) {
			$url .= '/create';
			return \yii\helpers\Html::a($this->t('app', "Create {title}"), $url);
		} else {
			$url .= "/$action";
			return \yii\helpers\Html::a($this->recordDesc($format, $max_len),
					[$url, 'id' => $this->getPrimaryKey() ]);
		}
	}

	public function increment(string $fldname, string $increment, array $conds = [], bool $usegaps = true): string
	{
		if( $increment == '' ) {
			$increment = "+1";
		} else if( $increment[0] != '+' ) {
			$increment = "+$increment";
		}
		$query = static::find()->select("MAX([[$fldname]])");
		$fldvalue = $this->$fldname??'';
		if( !empty($conds) ) {
			$query->andwhere($conds);
		}
		$base = '';
		if (preg_match('#^(.*?)(\d+)$#', $fldvalue, $matches)) {
			$base = $matches[1];
			$query->andWhere([ 'LIKE', $fldname, $base ]);
			if( $usegaps ) {
				$query->andWhere( [ "NOT IN", "CAST([[$fldname]] AS SIGNED) $increment",
					static::find()->select("[[$fldname]]") ] );
			}
		} else { // Alfanumérico
		}
//     try {
		$val = $query->scalar();
		if( $val === null ) {
			if( $base != '' ) {
				return $fldvalue;
			}
		}
        $fval =  AppHelper::incrStr($val??0, $increment);
        return $fval;
//     } catch( dbError &e ) { // sqlite3
//         if( e.getNumber() == 1137 ) { // ERROR 1137 (HY000): Can't reopen table:
//             sql = "SELECT MAX(" + nameToSQL( fldname ) + ")";
//             sql+= " FROM " + nameToSQL( tablename );
//             if( !conds.isEmpty() )
//                 sql+=" WHERE (" + conds + ")";
//             return selectInt( sql ) + 1;
//         } else throw;
//     }
    }

	public function setDefaultValues($context = null, bool $duplicating = false)
	{
	}

	public function saveOrFail(bool $runValidations = true)
	{
		if( !$this->save($runValidations) ) {
			throw new \Exception("Save " . static::getModelInfo('title') . ': ' . print_r($this->getErrors(), true) );
		}
	}

	/**
	 * Upsert (INSERT on duplicate keys UPDATE)
	 * https://github.com/yiisoft/active-record/issues/74
	 *
	 * @param boolean $runValidation
	 * @param array $attributes
	 * @return boolean
	 */
	public function upsert($runValidation = true, $attributes = null)
	{
		if ($runValidation) {
			// reset isNewRecord to pass "unique" attribute validator because of upsert
			$this->setIsNewRecord(false);
			if (!$this->validate($attributes)) {
				\Yii::info('Model not inserted due to validation error.', __METHOD__);
				return false;
			}
		}

		if (!$this->isTransactional(self::OP_INSERT)) {
			return $this->upsertInternal($attributes);
		}

		$transaction = static::getDb()->beginTransaction();
		try {
			$result = $this->upsertInternal($attributes);
			if ($result === false) {
				$transaction->rollBack();
			} else {
				$transaction->commit();
			}

			return $result;
		} catch (\Exception $e) {
			$transaction->rollBack();
			throw $e;
		} catch (\Throwable $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Insert or update record.
	 *
	 * @param array $attributes
	 * @return boolean
	 */
	protected function upsertInternal($attributes = null)
	{
		if (!$this->beforeSave(true)) {
			return false;
		}

		// attributes for INSERT
		$insertValues = $this->getAttributes($attributes);

		// attributes for UPDATE exclude primaryKey
		$updateValues = array_slice($insertValues, 0);
		foreach (static::getDb()->getTableSchema(static::tableName())->primaryKey as $key) {
			unset($updateValues[$key]);
		}

		// process update/insert
		if (static::getDb()->createCommand()->upsert(static::tableName(), $insertValues, $updateValues ?: false)->execute() === false) {
			return false;
		}

		// set isNewRecord as false
		$this->setOldAttributes($insertValues);

		// call handlers
		$this->afterSave(true, array_fill_keys(array_keys($insertValues), null));

		return true;
	}


	static public function createFromDefault($number = 1)
    {
		$ret = [];
		for( $count = 0; $count < $number; ++$count ) {
			$modelname = get_called_class();
			$model = new $modelname;
			$model->setDefaultValues();
			if( $number == 1 ) {
				return $model;
			} else {
				$ret[] = $model;
			}
		}
		return $ret;
    }

    public function controllerName($prefix = '')
    {
		$c = self::getModelInfo('controller_name');
		if( !$c ) {
			$c = AppHelper::stripNamespaceFromClassName($this);
			$c = lcfirst(str_replace("Search", "", $c));
		}
		return $prefix . $c;
    }

    public function viewPath(string $prefix = ''): string
    {
		$c = AppHelper::stripNamespaceFromClassName($this);
		$c = lcfirst(str_replace("Search", "", $c));
		return "$prefix$c/";
    }

    public function relatedFieldForModel($related_model): string|array|null
    {
		$tc = get_class($this);
		$oc = get_class($related_model);
		$cn = $related_model->className();
		$relations = self::$relations;
		foreach (self::$relations as $relname => $rel_info) {
			if ($rel_info['modelClass'] == $cn) {
				if ($rel_info['type'] == 'ManyToMany') {
					/// @todo
					return [$relname, $rel_info['right']];
				}
				$related_field = $rel_info['left'];
				list($table, $field) = AppHelper::splitFieldName($related_field);
				return $field;
			}
		}
		// If it's a derived class like *Form, *Search, look up its parent
		$cn = get_parent_class($related_model);
		foreach (self::$relations as $relname => $rel_info) {
			if( $rel_info['modelClass'] == $cn ) {
				if ($rel_info['type'] == 'ManyToMany') {
					continue;
				}
				$related_field = $rel_info['left'];
				list($table, $field) = AppHelper::splitFieldName($related_field);
				return $field;
			}
		}
		return null;
    }

	public function addErrorsFrom(ActiveRecord $model, $key = null)
	{
		if ($key !== null) {
			$key = $key . '.';
		}
		foreach ($model->getErrors() as $k => $error) {
			foreach ($error as $err_msg) {
				$this->addError($key . $k, $err_msg);
			}
		}
	}

	public function addErrorFromException(\Throwable $e)
	{
		$message = $e->getMessage();
		$devel_info = YII_ENV_PROD ? '' : "\n$message";
		$error = "Data was not saved in order to maintain the database integrity.";
		$error_data = [ 'offending' => '' ];
		if  ($e instanceof \yii\db\IntegrityException) {
			switch (intval($e->getCode())) {
				case 23000:
					if (preg_match('/UNIQUE constraint failed:\s*(.*)/i', $message, $matches)) {
						$error = "The '{offending}' data is duplicated";
						$error_data = [ 'offending' => $matches[1] ];
					}
					break;
			}
		}
		$this->addError(get_class($e), Yii::t('churros', $error, $error_data) . $devel_info);
	}

	public function getOneError():string
	{
		$errors = $this->getFirstErrors(false);
		if( count($errors) ) {
			return reset($errors);
		} else {
			return '';
		}
	}

	/**
	 * Returns at least one field that can be used as a code for this model
	 */
	static public function findCodeField()
	{
		$fields = explode(',',static::getModelInfo('code_field'))
			+ explode(',',static::getModelInfo('desc_field'));
		if( count($fields) ) {
			return array_pop($fields);
		} else {
			return [ $this->getPrimaryKey(), '' ];
		}
	}

	static public function findCodeAndDescFields(string $relname = null): array
	{
		if( $relname == null ) {
			$r0 = array_filter(explode(',',static::getModelInfo('code_field')));
			$r1 = array_filter(explode(',',static::getModelInfo('desc_field')));
			return array_merge($r0,$r1);
		} else if (isset(static::$relations[$relname])) {
			$relmodelname = static::$relations[$relname]['modelClass'];
			$relmodel = $relmodelname::instance();
			return $relmodel::findCodeAndDescFields();
		} else {
			return [];
		}
	}

	static public function findDescFields(string $relname = null): array
	{
		if( $relname == null ) {
			return array_filter(explode(',',static::getModelInfo('desc_field')));
		} else if (isset(static::$relations[$relname])) {
			$relmodelname = static::$relations[$relname]['modelClass'];
			$relmodel = $relmodelname::instance();
			return $relmodel::findDescFields();
		} else {
			return [];
		}
	}

	public function handyFieldValues(string $field, string $format,
		string $model_format = 'medium', array|string $scope=null, ?string $filter_fields = null)
	{
		throw new \Exception("field '$field' not supported in " . get_called_class() . "::handyFieldValues() ");
	}

	public function formatHandyFieldValues($field, $values, $format)
	{
		if( $format == 'ids' ) {
			return array_keys($values);
		} else if( $format == 'values' ) {
			return array_values($values);
		} else if( $format == 'value' ) {
			return $values[$this->$field]??null;
		} else if( $format == 'select2' || $format == 'group' ) {
			return ArrayHelper::map($values, 1,2,0);
		} else if( $format == 'selectize' ) {
			$ret = [];
			foreach( $values as $k => $v ) {
				$ret[] = [ 'value' => $k, 'text' => $v ];
			}
			return $ret;
		} else {
			return $values;
		}
	}

	public function asDate($fldname): ?YADTC
	{
		return YADTC::fromSql( $this->$fldname );
	}

	public function asCurrency($fldname, $currency = null, $options = [], $textOptions = []): string
	{
		return Yii::$app->formatter->asCurrency($this->$fldname, $currency, $options, $textOptions);
	}

	public function resetPrimaryKeys()
	{
		foreach( $this->primaryKey() as $key_name ) {
			$this->$key_name = null;
		}
	}

	/**
	 * Code copied from ActiveRecord::findByCondition.
	 * Always Adds the tablename to the primary key field
	 */
	static public function byPrimaryKey($condition)
	{
        $query = static::find();

        if (!ArrayHelper::isAssociative($condition) && !$condition instanceof ExpressionInterface) {
            // query by primary key
            $primaryKey = static::primaryKey();
            if (isset($primaryKey[0])) {
                $pk = $primaryKey[0];
				$pk = static::tableName() . '.' . $pk;
                // if condition is scalar, search for a single primary key, if it is array, search for multiple primary key values
                $condition = [$pk => is_array($condition) ? array_values($condition) : $condition];
            } else {
                throw new InvalidConfigException('"' . get_called_class() . '" must have a primary key.');
            }
        } elseif (is_array($condition)) {
            $aliases = static::filterValidAliases($query);
            $condition = static::filterCondition($condition, $aliases);
        }

        return $query->andWhere($condition);
	}


	public function relationsLabels(int $level): array
	{
		$tbname = $this->bareTableName();
		$ret[$tbname] = $title = static::getModelInfo('title_plural');
		self::recRelationsLabels($ret, $this, $level, $tbname, $title);
		return $ret;
	}

	static public function recRelationsLabels(array &$ret, $model, int $level, string $parent_relname, string $parent_title): void
	{
		if ($level == 0 ) {
			return;
		}
		foreach ($model::$relations as $relname => $relinfo ) {
			$rel_model = $relinfo['modelClass'];
			$relname = "$parent_relname.$relname";
			if ($relinfo['type'] == 'HasMany' || $relinfo['type'] == 'ManyToMany') {
				$title = $rel_model::getModelInfo('title_plural');
			} else {
				$title = $rel_model::getModelInfo('title');
			}
			$ret[$relname] = $title . ' (' . $parent_title . ')';
			self::recRelationsLabels($ret, $rel_model, $level-1, $relname, $title);
		}
	}

	public function reportFilterWhere(&$query, $fldname, $value)
	{
		$value = FormHelper::toOpExpression($value, false );
		if (!isset($value['v']) || $value['v'] === null) {
			return;
		}
		$this->addFieldToFilterWhere($query, $fldname, $value);
	}

	/**
	 * Función compartida por search y report
	 */
	public function searchFilterWhere(&$query, string $fldname, $value, bool $is_and = true)
	{
		if (!isset($value['v']) || $value['v'] === null || $value['v'] === '' || (is_array($value['v']) && empty($value['v'])) ) {
			return;
		}
 		$fullfldname = $this->tableName() . "." . $fldname;
		$this->addFieldToFilterWhere($query, $fullfldname, $value, $is_and);
	}

	public function addFieldToFilterWhere(&$query, string $fldname, array $value, bool $is_and = true)
	{
		return $this->baseAddFieldToFilterWhere($query, $fldname, $value, $is_and);
	}


	public function baseAddFieldToFilterWhere(&$query, string $fldname, array $value, bool $is_and = true)
	{
		if( is_array($value['v']) ) {
 			$query->andWhere([ 'in', $fldname, $value['v']]);
		} else switch( $value['op'] ) {
			case "=":
				if ($is_and) {
					$query->andWhere([$fldname => $value['v']]);
				} else {
					$query->andWhere([$fldname => $value['v']]);
				}
				break;
			case "<>":
			case ">=":
			case "<=":
			case ">":
			case "<":
			case "NOT LIKE":
			case "LIKE":
				if ($is_and) {
					$query->andWhere([ $value['op'], $fldname, $value['v'] ]);
				} else {
					$query->orWhere([ $value['op'], $fldname, $value['v'] ]);
				}

				break;
			case "START":
				if ($is_and) {
					$query->andWhere([ 'LIKE', $fldname, $value['v'] . '%', false]);
				} else {
					$query->orWhere([ 'LIKE', $fldname, $value['v'] . '%', false]);
				}
				break;
			case "NOT START":
				if ($is_and) {
					$query->andWhere([ 'NOT LIKE', $fldname, $value['v'] . '%', false]);
				} else {
					$query->orWhere([ 'NOT LIKE', $fldname, $value['v'] . '%', false]);
				}
				break;
			case "BETWEEN":
			case "NOT BETWEEN":
				if ($is_and) {
					$query->andWhere([ $value['op'], $fldname, explode(',',$value['v']) ]);
				} else {
					$query->orWhere([ $value['op'], $fldname, explode(',',$value['v']) ]);
				}
				break;
			case "bool":
				if ($is_and) {
					$query->andWhere([$fldname => ($value['v'] == 'true') ? 1 : ($value['v'] == 'false' ? 0 : boolval($value['v']))]);
				} else {
					$query->orWhere([ $value['op'], $fldname, explode(',',$value['v']) ]);
				}
				break;
		}
	}

	/**
	 * Saves the record handling calculated fields
	 */
	public function save($runValidation = true, $validateFields = null)
	{
		if (property_exists($this, '_calculated_fields') && count(static::$_calculated_fields)) {
			$scf = [];
			foreach (static::$_calculated_fields as $cf) {
				if (property_exists($this, $cf)) {
					$scf[$cf] = $this->$cf;
					unset($this->$cf);
				}
			}
			try {
				if (parent::save($runValidation, $validateFields)) {
					$values = self::find()->where($this->getPrimaryKey(true))->select(array_keys($scf))->asArray();
					$this->setAttributes($values);
					return true;
				} else {
					foreach ($scf as $kcf => $cf) {
						$this->$kcf = $cf;
					}
					return false;
				}
			} catch (\Exception $e) {
				foreach ($scf as $kcf => $cf) {
					$this->$kcf = $cf;
				}
				throw $e;
			}
		} else {
			return parent::save($runValidation, $validateFields);
		}

	}

	/**
	 * Get all properties that start with a given prefix.
	 *
	 * @param string $prefix The prefix to filter properties by.
	 * @return array An array of property names that start with the specified prefix.
	 */
	public function getPropertiesWithPrefix($prefix)
	{
		// Get all class properties
		$classProperties = get_object_vars($this);

		// Filter properties by prefix
		$filteredProperties = [];
		$lp = strlen($prefix);
		foreach ($classProperties as $kp => $p) {
			if (strpos($kp, $prefix) === 0) {
				$kp = substr($kp, $lp);
				$filteredProperties[$kp] = $p;
			}
		}

		return $filteredProperties;
	}

} // trait ModelInfoTrait

