<?php

/**
 * RelationTrait
 *
 * @author Santilín <z@zzzzz.es>
 * Based on a work by Yohanes Candrajaya <moo.tensai@gmail.com>
 */

namespace santilin\churros;

use Yii;
use yii\base\InvalidConfigException;
use yii\db\{ActiveQuery,ActiveRecord,Exception,IntegrityException};
use yii\helpers\{Inflector,StringHelper,ArrayHelper};
use santilin\churros\json\JsonModel;
use santilin\churros\helpers\AppHelper;

/*
 *  add this line to your Model to enable soft delete
 *
 * private $_rt_softdelete;
 *
 * function __construct(){
 *      $this->_rt_softdelete = [
 *          '<column>' => <undeleted row marker value>
 *          // multiple row marker column example
 *          'isdeleted' => 1,
 *          'deleted_by' => \Yii::$app->user->id,
 *          'deleted_at' => date('Y-m-d H:i:s')
 *      ];
 * }
 * add this line to your Model to enable soft restore
 * private $_rt_softrestore;
 *
 * function __construct(){
 *      $this->_rt_softrestore = [
 *          '<column>' => <undeleted row marker value>
 *          // multiple row marker column example
 *          'isdeleted' => 0,
 *          'deleted_by' => 0,
 *          'deleted_at' => 'NULL'
 *      ];
 * }
 */

trait RelationTrait
{
    /**
     * Load all attributes including related attributes
     * @param $post
     * @param array $relations_in_form
     * @return bool
     */
    public function loadAll(array $post, array $relations_in_form = [], ?string $formName = null): bool
    {
        if ($formName === null) {
			$formName = $this->formName();
		}
		if (isset($post[$formName])) {
            $relations_in_model = static::$relations;
			foreach ($relations_in_form as $rel_key => $relation_in_form) {
				if (!isset($relations_in_model[$relation_in_form])) {
					continue;
				}
				$model_relation = $relations_in_model[$relation_in_form];
				// Look for embedded relations data in the main form
				$post_data = null;
				if ($model_relation['type'] == 'HasOne' || $model_relation['type'] == "OneToOne") {
					if (isset($post[$formName][$relation_in_form])) {
						$post_data = $post[$formName][$relation_in_form];
						unset($post[$formName][$relation_in_form]);
					} else if (isset($post[$formName][$model_relation['model']])) {
						$post_data = $post[$formName][$model_relation['model']];
						unset($post[$formName][$model_relation['model']]);
					} else if (isset($post[$model_relation['model']])) {
						$post_data = $post[$model_relation['model']];
						unset($post[$model_relation['model']]);
					} else if (is_array($model_relation['left'])) {
						foreach ($model_relation['left'] as $full_mr_left) {
							$mr_left = AppHelper::lastWord($full_mr_left, '.');
							if (isset($post[$formName][$mr_left])) {
								$post_data = $post[$formName][$mr_left];
								unset($post[$formName][$mr_left]);
								break;
							} else if (isset($post[$mr_left])) {
								$post_data = $post[$mr_left];
								unset($post[$mr_left]);
								break;
							}
						}
					}
					if ($post_data !== null) {
						$rel_model = new $model_relation['modelClass'];
						if (is_array($model_relation['left'])) {
							// Sets the foreign keys of this model if multiple keys
							if (is_string($post_data)) {
								$post_data = json_decode($post_data);
								$this->setAttributes(array_combine($rel_model->primaryKey(),$post_data), false); // not safe
							} else {
								throw new \Exception("stop");
							}
						} else if (is_array($post_data)) {
							// creates a new relmodel and populates it
							$rel_model->setAttributes($post_data);
							$this->populateRelation($relation_in_form, $rel_model);
						}
					}
				} else {
                    // HasMany or Many2Many
					if (array_key_exists($relation_in_form, $post)) {
						$post_data = $post[$relation_in_form]?:[];
						unset($post[$relation_in_form]);
					} else if (array_key_exists($rel_key, $post)) {
						$post_data = $post[$rel_key]?:[];
						unset($post[$rel_key]);
					} else if (array_key_exists($formName, $post) && array_key_exists($relation_in_form, $post[$formName])) {
						$post_data = $post[$formName][$relation_in_form]?:[];
						unset($post[$formName][$relation_in_form]);
					} else if (array_key_exists($model_relation['model'], $post)) {
						$post_data = $post[$model_relation['model']]?:[];
						unset($post[$model_relation['model']]);
					}
					if ($post_data !== null) {
						// find first relation with the same model to coalesce differente scoped relations
						// foreach ($relations_in_model as $rn => $rinfo) {
						// 	if ($rinfo['model'] == $model_relation['model']) {
						// 		$relation_in_form = $rn;
						// 		break;
						// 	}
						// }
                        $this->loadToRelation($relation_in_form, (array)$post_data);
                    }
                }
            }
            return $this->load($post, $formName);
        }
        return false;
    }

    /**
     * Refactored from loadAll() function
     * @param $relation skimmed relation data
     * @param $relation_name
     * @param $form_values form values
     * @return bool
     */
    public function loadToRelation(string $relation_name, array|string $form_values)
    {
		$container = [];
        $relation = $this->getRelation($relation_name);
		$link = $relation->link;
		if ($relation->via != null) { // Many to many with junction model
			$relation_name = $relation->via[0];
			$relation = $this->getRelation($relation_name);
			$junction_link = $relation->link;
		} else {
			$junction_link = [];
		}
		if ($relation->multiple) {
			$relModelClass = $relation->modelClass;
			if (is_string($form_values)) {
				if (str_contains($form_values, ',')) {
					// TreeWidget: No array is passed, a comma separated string instead
					$form_values = explode(',', $form_values);
				} else {
					// TreeWidget: If there is only one selected item, no array is posted
					$form_values = [ $form_values ];
				}
			}
            foreach ($form_values as $relPost) {
				if (empty($relPost)) {
					continue;
				}
                if (is_array($relPost)) {
					$relPost = array_filter($relPost);
					// many2many relation with _POST = array of records
					if ($relPost) {
						$relModel = new $relModelClass;
						$condition = [];
						if (ArrayHelper::isAssociative($relPost)) {
							foreach ($relModel->primaryKey() as $pk) {
								if (isset($relPost[$pk])) {
									$condition[$pk] = $relPost[$pk];
								}
							}
							foreach ($link as $this_fk => $other_pk) {
								$relPost[$this_fk] = $condition[$this_fk] = $this->$other_pk;
							}
						} else {
							// many2many relation with _POST = array of arrays of related ids
							foreach ($relModel->primaryKey() as $pk) {
								$pkv = array_shift($relPost);
								$condition[$pk] = $pkv;
							}
							$relPost = $condition;
						}
						$relModel = $relModelClass::findOne($condition);
						if (is_null($relModel)) {
							$relModel = new $relModelClass;
						}
						$relModel->load($relPost, '');
						$container[] = $relModel;
					}
                } else {
					// many2many relation with _POST = array of related ids
					$m2mkeys = [];
					foreach ($link as $this_fk => $other_pk) {
						$m2mkeys[$other_pk] = $relPost;
					}
					foreach ($junction_link as $rel_fk => $this_pk) {
						$m2mkeys[$rel_fk] = $this->$this_pk;
					}
					$relModel = $relModelClass::findOne($m2mkeys);
					if (!$relModel) {
						$relModel = new $relModelClass;
						$relModel->setAttributes($m2mkeys, false);
					}
					$container[] = $relModel;
                }
            }
		} else { // Many2Many
			throw new \Exception('stop');

			$other_fk = reset($relation->link);
			foreach ($form_values as $relPost) {
				if (is_array($relPost)) {
					$relObj = empty($relPost[$relPKAttr[0]]) ? new $relModelClass : $relModelClass::findOne($relPost[$relPKAttr[0]]);
					$relObj->load($relPost);
					$container[] = $relObj;
				} else {
					$relObj = $relModelClass::findOne($relPost);
					if ($relObj) {
						$container[] = $relObj;
					}

				}
			}
        }
		$this->populateRelation($relation_name, $container);
    }

    /**
     * Save model including all related models already loaded
     * @param array $relations The relations to consider for this form
     * @return bool
     * @throws Exception
     */
    public function saveAll(bool $runValidation = true, $attributeNames = null): bool
    {
		$must_commit = false;
		$trans = $this->getDb()->getTransaction();
		if (!$trans) {
			$trans = $this->getDb()->beginTransaction();
			$must_commit = true;
		}
		$wasNewRecord = $this->isNewRecord;
        try {
			$relatedRecords = $this->relatedRecords;
			if ($this->save(true, $attributeNames)) {
				if ($this->saveRelated($wasNewRecord, $relatedRecords)) {
					if ($must_commit) {
						$trans->commit();
					}
					return true;
				} else {
					if ($must_commit) {
						$trans->rollback();
					}
                    $this->isNewRecord = $wasNewRecord;
                    return false;
				}
            } else {
                return false;
            }
		} catch (\yii\db\IntegrityException $e) {
			if ($must_commit) {
				$trans->rollBack();
			}
            $this->isNewRecord = $wasNewRecord;
            $this->addErrorFromException($e);
			return false;
        }
    }

    public function usedInRelation(string $relation_name): int
    {
		$rel_method_name = 'get' . ucfirst($relation_name);
		if (method_exists($this, $rel_method_name)) {
			return call_user_func([$this, $rel_method_name])->exists();
		} else {
			return 0;
		}
    }


    public function saveRelated(bool $wasNewRecord, array $relatedRecords): bool
    {
		$success = true;
		foreach (array_keys($this->relatedRecords) as $rn) {
			unset($this->$rn);
		}
		foreach ($relatedRecords as $rn => $rvs) {
			$this->populateRelation($rn, $rvs);
		}
		foreach ($this->relatedRecords as $relation_name => $records) {
			/* @var $records ActiveRecord | ActiveRecord[] */
			if ($records instanceof \yii\db\BaseActiveRecord && !$records->getIsNewRecord()) {
				continue;
			} else if ($records instanceof \yii\db\BaseActiveRecord) {
				$records = (array)$records;
			}
			$success = $this->updateRecords($relation_name, $records, $wasNewRecord);
		}
		return $success;
    }

    private function updateRecords(string $relation_name, array $records, bool $isNewRecord): bool
    {
		$success = true;
		$relation = $this->getRelation($relation_name);
		$link = $relation->link;
        // $isSoftDelete = isset($this->_rt_softdelete);
		if ($relation->via != null) { // real model, not junction model
			$relation_name = $relation->via[0];
			$relation = $this->getRelation($relation_name);
		}
		if ($relation->multiple) { // hasmany or many2many
			$dontDeletePk = [];
			$relModelClass = $relation->modelClass;
			$relPKAttr = $relModelClass::primarykey();
			if (count($relPKAttr) == 0) { // HasMany without primary key
				foreach ($records as $index => $relModel) {
					// Set relModel foreign key
					foreach ($link as $foreign_key => $value) {
						if (is_object($relModel)) {
							$relModel->$foreign_key = $this->$value;
						}
					}
					$relSave = $relModel->save();
					if (!$relSave || !empty($relModel->errors)) {
						$relModelWords = Yii:: t('churros', Inflector::camel2words(StringHelper::basename($relModelClass)));
						$index++;
						foreach ($relModel->errors as $validation) {
							foreach ($validation as $errorMsg) {
								$this->addError($relation_name, "$relModelWords #$index : $errorMsg");
							}
						}
						$success = false;
					}
				}
			} else {
				if (!$isNewRecord) {
					/* @var $relModel ActiveRecord */
					$relation_getter = "get" . ucfirst($relation_name);
					$query = $this->$relation_getter();
					foreach ($records as $index => $relModel) {
						if (!is_array($relModel)) {
							$dontDeletePk = $relModel->getPrimaryKey(true);
						} else {
							$dontDeletePk = $relModel;
						}
						$dont_delete_query = [];
						foreach ($dontDeletePk as $attr => $value) {
							$dont_delete_query[$attr] = $value;
						}
						$query->andWhere(['not', [ 'and', $dont_delete_query]]);
					}
					try {
						$records_to_delete = $query->all();
						foreach ($records_to_delete as $record_to_delete) {
							// if ($isSoftDelete) {
								// $record_to_delete->_rt_softdelete, $query);
							// } else {
								$record_to_delete->delete();
							// }
						}
					} catch (IntegrityException $exc) {
						$this->addError($relation_name, "Data can't be deleted because it's still used by another data.");
						$success = false;
					}
				}
				// Save all posted records
				foreach ($records as $index => $relModel) {
					if (!empty($relModel->getRelaxedDirtyAttributes())) {
						foreach ($link as $fk => $pk) {
							$relModel->$fk = $this->$pk; // in case is a new record
						}
						$relSave = $relModel->save();
						if (!$relSave || !empty($relModel->errors)) {
							$relModelWords = Yii:: t('churros', Inflector::camel2words(StringHelper::basename($relModelClass)));
							$index++;
							foreach ($relModel->errors as $validation) {
								foreach ($validation as $errorMsg) {
									$this->addError($relation_name, "$relModelWords #$index : $errorMsg");
								}
							}
							$success = false;
						}
					}
				}
			}
		} else {
			//Has One
			foreach ($link as $key => $value) {
				$records->$key = $this->$value;
			}
			$relSave = $records->save();
			if (!$relSave || !empty($records->errors)) {
				$recordsWords = Yii:: t('churros', Inflector::camel2words(StringHelper::basename($AQ->modelClass)));
				foreach ($records->errors as $validation) {
					foreach ($validation as $errorMsg) {
						$this->addError($relation_name, "$recordsWords : $errorMsg");
					}
				}
				$success = false;
			}
		}
		return $success;
    }

    /**
     * Delete model row with all related records
     * @param array $relations The relations to consider for this form
     * @return bool
     * @throws Exception
     */
    public function deleteWithRelated(array $relations = []): bool
    {
        /* @var $this ActiveRecord */
        $db = $this->getDb();
        $trans = $db->beginTransaction();
        $isSoftDelete = isset($this->_rt_softdelete);
        try {
            $error = false;
            foreach ($relations as $relation_name) {
				$relation = $this->getRelation($relation_name);
                $conds = [];
                if ($relation->multiple) {
                    $link = $relation->link;
                    if (count($this->$relation_name)) {
                        foreach ($link as $key => $value) {
                            if (isset($this->$value)) {
                                $conds[$key] = $this->$value;
                            }
                        }
                        if (empty($conds)) {
							throw new \Exception("Removing from `$relation_name` relation is not possible");
						}
                        if ($isSoftDelete) {
                            $error = !$this->{$relation_name}[0]->updateAll($this->_rt_softdelete, ['and', $conds]);
                        } else {
                            $error = !$this->{$relation_name}[0]->deleteAll(['and', $conds]);
                        }
                    }
                }
            }
            if ($error) {
                $trans->rollback();
                return false;
            }
            if ($isSoftDelete) {
                $this->attributes = array_merge($this->attributes, $this->_rt_softdelete);
                if ($this->save(false)) {
                    $trans->commit();
                    return true;
                } else {
                    $trans->rollBack();
                }
            } else {
                if ($this->delete()) {
                    $trans->commit();
                    return true;
                } else {
                    $trans->rollBack();
                }
            }
        } catch (Exception $e) {
            $trans->rollBack();
            throw $e;
        }
    }

    /**
     * Restore soft deleted row including all related records
     * @param array $relations The relations to consider for this form
     * @return bool
     * @throws Exception
     */
    public function restoreWithRelated($relations = [])
    {
        if (!isset($this->_rt_softrestore)) {
            return false;
        }

        /* @var $this ActiveRecord */
        $db = $this->getDb();
        $trans = $db->beginTransaction();
        try {
            $error = false;
			foreach ($relations as $relation_name) {
				$relation = $this->getRelation($relation_name);
                $array = [];
                if ($relation->ismultiple) {
                    $link = $relation->link;
                    if (count($this->$relation_name)) {
                        foreach ($link as $key => $value) {
                            if (isset($this->$value)) {
                                $array[$key] = $this->$value;
                            }
                        }
                        $error = !$this->{$relation->name}[0]->updateAll($this->_rt_softrestore, ['and', $array]);
                    }
                }
            }
            if ($error) {
                $trans->rollback();
                return false;
            }
            $this->attributes = array_merge($this->attributes, $this->_rt_softrestore);
            if ($this->save(false)) {
                $trans->commit();
                return true;
            } else {
                $trans->rollBack();
            }
        } catch (Exception $exc) {
            $trans->rollBack();
            throw $exc;
        }
    }

    public function createChild(string $relation_name, string $form_class_name = null)
    {
        if (isset(static::$relations[$relation_name])) {
            $r = static::$relations[$relation_name];
            $rel_model_class = $r['modelClass'];
            if ($form_class_name != null) {
                $child = new $form_class_name;
                if (!($child instanceof $rel_model_class)) {
                    throw new InvalidConfigException("$form_class_name is not derived from $rel_model_class");
                }
            } else {
                $child = new $rel_model_class;
            }
            return $child;
        } else {
            return null;
        }
    }

    public function createChildren($relation_name, $form_class_name)
	{
		// Get the relation query
		$relation = $this->getRelation($relation_name);

		// Create a query for the relation
		$query = $this->createRelationQuery($form_class_name, $relation->link, $relation->multiple);
		$query->where = $relation->where;

		// Set the modelClass to your form class
		$query->modelClass = $form_class_name;

		// Return all related records as instances of $form_class_name
		return $query->all();
	}



    public function createRelatedModels(string $relation_name, array $current_values = [],
                                      string $form_class_name = null): array|JsonModel
    {
        $rel_info = static::$relations[$relation_name];
        $rel_model_class = $rel_info['modelClass'];
        if ($form_class_name != null) {
            $child = new $form_class_name;
            if (!($child instanceof $rel_model_class)) {
                throw new InvalidConfigException("$form_class_name is not derived from $rel_model_class");
            }
        }
        if ($rel_info['type'] == 'HasMany') {
            $related_models = $this->$relation_name;
            if (!empty($current_values)) {
                foreach ($related_models as $nr => $rm) {
                    if (isset($current_values[$nr])) {
                        $related_models[$nr]->setAttributes($current_values[$nr]);
                    }
                }
            }
            return $related_models;
        } else {
            if ($form_class_name != null) {
                $child = new $form_class_name;
                if (!($child instanceof $rel_model_class)) {
                    throw new InvalidConfigException("$form_class_name is not derived from $rel_model_class");
                }
            } else {
                $child = new $rel_model_class;
            }
            return $child;
        }
    }

    protected function findRelationByModel(string $model_name): ?array
	{
		foreach ($this::$relations as $relation_name => $rel_info) {
			if ($rel_info['model'] == $model_name) {
				// $rel_info['name'] = $relation_name;
				return $rel_info;
			}
		}
		return null;
	}

	public function findOtherRelationByModel(string $model_name): ?array
	{
		if ($this::$isJunctionModel) {
			foreach ($this::$relations as $relation_name => $rel_info) {
				if ($rel_info['model'] != $model_name) {
					return $rel_info;
				}
			}
		}
		return null;
	}

	public function modelsToRelations(string $model_name): string
	{
		$model = $this;
		$relation = null;
		$ret = '';
		while (true) {
			if (strpos($model_name, '.') === FALSE) {
				$relation = $this->findRelationByModel($model_name);
				$rest = '';
			} else {
				list($model_name, $rest) = AppHelper::splitString($model_name, '.');
				$relation = $this->findRelationByModel($model_name);
			}
			if ($ret) {
				$ret .= '.';
			}
			if ($relation) {
				$ret .= $relation['name'];
				$model_name = $rest;
			} else if ($rest) {
				$ret .= $rest;
			} else {
				$ret .= $model_name;
				break;
			}
		}
		return $ret;
	}

	public function reloadRelations(array $relations)
	{
		foreach ($relations as $relationName) {
			// Unset cached relation data to force reload
			unset($this->$relationName);
			// Access relation for loading
			$this->$relationName;
		}
		return $this;
	}

}
