<?php

/**
 * RelationTrait
 *
 * @author Yohanes Candrajaya <moo.tensai@gmail.com>
 * @since 1.0
 */

namespace santilin\churros;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\db\IntegrityException;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\ArrayHelper;

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
    public function loadAll($post, $relations_in_form = [], $formName = null): bool
    {
        if( $formName === null ) {
			$formName = $this->formName();
		}
        if ($this->load($post, $formName)) {
			if (count($relations_in_form)==0) {
				return true;
			}
            $relations_in_model = static::$relations;
            foreach($relations_in_model as $rel_name => $model_relation ) {
				if( !in_array($rel_name, $relations_in_form) ) {
					continue;
				}
				if( $model_relation['type'] == 'HasOne' || $model_relation['type'] == "OneToOne" ) {
					// Look for embedded relations data in the main form
					$post_data = null;
					if( isset($post[$formName][$rel_name]) && is_array($post[$formName][$rel_name]) ) {
						$post_data = $post[$formName][$rel_name];
					} else if( isset($post[$formName][$model_relation['model']]) && is_array($post[$formName][$model_relation['model']]) ) {
						$post_data = $post[$formName][$model_relation['model']];
					} else if( isset($post[$model_relation['model']]) && is_array($post[$model_relation['model']]) ) {
						$post_data = $post[$model_relation['model']];
					}
					if( $post_data ) {
						$rel_model = new $model_relation['modelClass'];
						$rel_model->setAttributes( $post_data );
						$this->populateRelation($rel_name, $rel_model);
					}
				} else {
                    // HasMany or Many2Many outside of formName
					$post_data = (isset($post[$rel_name]) && is_array($post[$rel_name]))
						? $post[$rel_name] : null;
					if( $post_data === null ) {
						$post_data = (isset($post[$formName][$rel_name]) && is_array($post[$formName][$rel_name])) ? $post[$formName][$rel_name] : null ;
					}
					if( $post_data === null ) {
						$post_data = (isset($post[$model_relation['model']]) && is_array($post[$model_relation['model']])) ? $post[$model_relation['model']] : null ;
					}
					if( $post_data ) {
                        $this->loadToRelation($rel_name, $post_data);
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Refactored from loadAll() function
     * @param $relation skimmed relation data
     * @param $rel_name
     * @param $v form values
     * @return bool
     */
    public function loadToRelation($rel_name, $v)
    {
        /* @var $this ActiveRecord */
        /* @var $relObj ActiveRecord */
        /* @var $relModelClass ActiveRecord */
        $relation = $this->getRelationData($rel_name);
		$relModelClass = $relation['modelClass'];
        $relPKAttr = $relModelClass::primaryKey();

		$container = [];
		if (count($relPKAttr) > 1) { // Many to many with junction model
			$link = $relation['link'];
			$link_keys = array_keys($link);
			$this_pk = reset($link_keys);
            foreach ($v as $relPost) {
				if( $relPost == null ) {
					continue;
				}
                if (is_array($relPost) ) {
					// many2many relation with _POST = array of records
					if( array_filter($relPost) ) {
						$condition = [];
						$condition[$relPKAttr[0]] = $this->primaryKey;
						foreach ($relPost as $relAttr => $relAttrVal) {
							if (in_array($relAttr, $relPKAttr)) {
								$condition[$relAttr] = $relAttrVal;
							}
						}
						$relObj = $relModelClass::findOne($condition);
						if (is_null($relObj)) {
							$relObj = new $relModelClass;
						}
						$relObj->load($relPost, '');
						$container[] = $relObj;
					}
                } else {
					// many2many relation with _POST = array of related ids
					$m2mkeys = [];
					foreach( $relPKAttr as $pk ) {
						if( $pk == $this_pk ) {
							$m2mkeys[$pk] = $this->primaryKey;
						} else {
							$m2mkeys[$pk] = intval($relPost);
						}
					}
					$container[] = $m2mkeys;
                }
            }
        } else if ($relation['via'] == null) {
			if (count($relPKAttr)) { // Has Many with primary key
				foreach ($v as $relPost) {
					if (is_array($relPost) ) {
						if( array_filter($relPost) ) {
							/* @var $relObj ActiveRecord */
							$relObj = (empty($relPost[$relPKAttr[0]])) ? new $relModelClass() : $relModelClass::findOne($relPost[$relPKAttr[0]]);
							if (is_null($relObj)) {
								$relObj = new $relModelClass();
							}
							$relObj->load($relPost, '');
							$container[] = $relObj;
						}
					} else {
						// Just primary key of records, just one field in primary key
						$container[] = [ $relPKAttr[0] => $relPost ];
					}
				}
			} else { // Has Many without primary key
				foreach ($v as $relPost) {
					if (is_array($relPost) ) {
						if( array_filter($relPost) ) {
							/* @var $relObj ActiveRecord */
							$relObj = new $relModelClass();
							$relObj->load($relPost, '');
							$container[] = $relObj;
						}
					} else {
						throw new \Exception("no primery key found");
					}
				}
			}
		} else { // Many2Many
			foreach( $v as $relPost ) {
				if( is_array($relPost) ) {
					$id = $relPost[$relPKAttr[0]];
					$relObj = empty($id) ? new $relModelClass : $relModelClass::findOne($id);
					$relObj->load($relPost);
				} else {
					$id = $relPost;
					$relObj = [ $relPKAttr[0] => $id ];
				}
				$container[] = $relObj;
			}
        }
		$this->populateRelation($rel_name, $container);
        return true;
    }

    /**
     * Save model including all related models already loaded
     * @param array $relations The relations to consider for this form
     * @return bool
     * @throws Exception
     */
    public function saveAll(bool $runValidation = true): bool
    {
		$must_commit = false;
		$trans = $this->getDb()->getTransaction();
		if( !$trans ) {
			$trans = $this->getDb()->beginTransaction();
			$must_commit = true;
		}
		$wasNewRecord = $this->isNewRecord;
        try {
            if ($this->save($runValidation)) {
				if ($this->saveRelated($wasNewRecord)) {
					if( $must_commit ) {
						$trans->commit();
					}
					return true;
				} else {
					if( $must_commit ) {
						$trans->rollback();
					}
                    $this->isNewRecord = $wasNewRecord;
                    return false;
				}
            } else {
                return false;
            }
		} catch (\yii\db\IntegrityException $e) {
			if( $must_commit ) {
				$trans->rollBack();
			}
            $this->isNewRecord = $wasNewRecord;
            $this->addErrorFromException($e);
			return false;
        }
    }

    public function usedInRelation(string $rel_name): int
    {
		$rel_method_name = 'get' . ucfirst($rel_name);
		if( method_exists($this, $rel_method_name)) {
			return call_user_func([$this, $rel_method_name])->exists();
		} else {
			return 0;
		}
    }


    public function saveRelated(bool $wasNewRecord): bool
    {
		$success = true;
		foreach ($this->relatedRecords as $rel_name => $records) {
			/* @var $records ActiveRecord | ActiveRecord[] */
			if ($records instanceof \yii\db\BaseActiveRecord && !$records->getIsNewRecord()) {
				continue;
			} else if ($records instanceof \yii\db\BaseActiveRecord) {
				$records = (array)$records;
			}
			if (count($records)>0) {
				$justUpdateIds = !($records[0] instanceof \yii\db\BaseActiveRecord);
				if( $justUpdateIds ) {
					$success = $this->updateIds($wasNewRecord, $rel_name, $records);
				} else {
					$success = $this->updateRecords($wasNewRecord, $rel_name, $records);
				}
			}
		}
		return $success;
    }

    private function updateIds(bool $wasNewRecord, string $rel_name, $records)
    {
		$relation = $this->getRelation($rel_name);
        $isSoftDelete = isset($this->_rt_softdelete);
		$dontDeletePk = [];
		$notDeletedFK = [];
		$success = true;
		$isManyMany = false;
		if ($relation->multiple ) { // Has many or many2many
			if ($relation->via != null ) {
				$relModelClass = $relation->via;
			} else {
				$relModelClass = $relation->modelClass;
			}
			$links = array_keys($relation->link);
			$relModel = new $relModelClass;
			$relPKAttr = $relModel->primarykey();
			if (count($relPKAttr) > 1) {
				$isManyMany = true;
				foreach ($relPKAttr as $attr ) {
					if (!in_array($attr, $links) ) {
						$other_fk = $attr;
					} else {
						$this_fk = $attr;
					}
				}
			}

			if (!$wasNewRecord) {
				// DELETE WITH 'NOT IN' PK MODEL & REL MODEL
				if ($isManyMany) {
					$query = [];
					foreach ($relation->link as $foreign_key => $value ) {
						$query = [ "AND", [ $foreign_key => $this->$value ] ];
					}
					foreach ($records as $pk_values) {
						$dontDeletePk[$other_fk][] = $pk_values[$other_fk];
					}
					foreach ($dontDeletePk as $attr => $value) {
						$query[] = ["NOT IN", $attr, $value];
					}
					try {
						if ($isSoftDelete) {
							$relModel->updateAll($this->_rt_softdelete, $query);
						} else {
							$relModel->deleteAll($query);
						}
					} catch (IntegrityException $exc) {
						$this->addError($rel_name, "Data can't be deleted because it's still used by another data.");
						$success = false;
					}
					$records_in_database = $relation->select($other_fk)->asArray()->all();
				} else {
					if( $relation->via != null ) { // Many2Many
						$records_in_database = $relation->asArray()->all();
					} else {
						$query = [];
						foreach ($relation->link as $foreign_key => $value ) {
							$query = [ "AND", [ $foreign_key => $this->$value ] ];
						}
						foreach ($records as $pk_values) {
							$dontDeletePk[] = $pk_values;
						}
						if (!empty($dontDeletePk)) {
							$query = ['and', $notDeletedFK, ['not in', $relPKAttr[0], $dontDeletePk]];
							try {
								if ($isSoftDelete) {
									$relModel->updateAll($this->_rt_softdelete, $query);
								} else {
									$relModel->deleteAll($query);
								}
							} catch (IntegrityException $exc) {
								$this->addError($rel_name, "Data can't be deleted because it's still used by another data.");
								$success = false;
							}
						}
					}
				}
			} else {
				$records_in_database = [];
			}
			// Get the ids already in the database
 			// Save ids
			foreach ($records as $index => $pk_values) {
				$must_save = true;
				foreach( $records_in_database as $key => $record ) {
					if( $pk_values[$other_fk] == $record[$other_fk] ) {
						$must_save = false;
						continue;
					}
				}
				if( !$must_save ) {
					continue;
				}
				$relModel->setIsNewRecord(true);
				$relModel->setAttributes($pk_values, false);
				$relModel->$this_fk = $this->getPrimaryKey();
				$relModel->$other_fk = $pk_values[$other_fk];
				$relSave = $relModel->save();
				if (!$relSave || !empty($relModel->errors)) {
					$relModelWords = $relModel->t('churros', "{title}");
					$index++;
					foreach ($relModel->errors as $validation) {
						foreach ($validation as $errorMsg) {
							$this->addError($rel_name, "$relModelWords #$index : $errorMsg");
						}
					}
					$success = false;
				}
			}
		} else {
// 			//Has One
// 			foreach ($link as $key => $value) {
// 				$records->$key = $this->$value;
// 			}
// 			$relSave = $records->save();
// 			if (!$relSave || !empty($records->errors)) {
// 				$recordsWords = Yii::t('churros', Inflector::camel2words(StringHelper::basename($AQ->modelClass)));
// 				foreach ($records->errors as $validation) {
// 					foreach ($validation as $errorMsg) {
// 						$this->addError($rel_name, "$recordsWords : $errorMsg");
// 					}
// 				}
// 				$error = true;
// 			}
		}
		return $success;
    }

    private function updateRecords(bool $wasNewRecord, string $rel_name, array $records): bool
    {
		$success = true;
		$relation = $this->getRelation($rel_name);
        $isSoftDelete = isset($this->_rt_softdelete);
		$link = $relation->link;
		/// SCT Add error info
		if( $relation->via != null ) {
			$records_copy = $records;
			foreach ($records as $index => $relModel) {
				$attributes = $relModel->attributes;
				if( $relModel->isNewRecord ) {
					$relSave = $relModel->save();
					if (!$relSave || !empty($relModel->errors)) {
						$relModelWords = Yii:: t('churros', Inflector::camel2words(StringHelper::basename($AQ->modelClass)));
						$index++;
						foreach ($relModel->errors as $validation) {
							foreach ($validation as $errorMsg) {
								$this->addError($rel_name, "$relModelWords #$index : $errorMsg");
							}
						}
						$success = false;
					} else {
					$rel_name = $rel_name . Inflector::camel2words(StringHelper::basename($AQ->primaryModel::className()));
						$records[$index] = new $rel_name;
						$rel_name->$link = $relModel->primaryKey;
					}
				}
			}
		}
		if( $success ) {
			$dontDeletePk = [];
			$notDeletedFK = [];
			if ($relation->multiple) {
				$relModelClass = $relation->modelClass;
				$relPKAttr = $relModelClass::primarykey();
				if (count($relPKAttr) == 0) { // HasMany without primary key
					foreach ($records as $index => $relModel) {
						// Set relModel foreign key
						foreach ($relation->link as $foreign_key => $value) {
							if( is_object($relModel) ) {
								$relModel->$foreign_key = $this->$value;
							}
						}
						$relSave = $relModel->save();
						if (!$relSave || !empty($relModel->errors)) {
							$relModelWords = Yii:: t('churros', Inflector::camel2words(StringHelper::basename($relModelClass)));
							$index++;
							foreach ($relModel->errors as $validation) {
								foreach ($validation as $errorMsg) {
									$this->addError($rel_name, "$relModelWords #$index : $errorMsg");
								}
							}
							$success = false;
						}
					}

				} else {
					$isManyMany = (count($relPKAttr) > 1);
					/* @var $relModel ActiveRecord */
					foreach ($records as $index => $relModel) {
						// Set relModel foreign key
						foreach ($relation->link as $foreign_key => $value) {
							if( is_object($relModel) ) {
								$relModel->$foreign_key = $this->$value;
							}
							$notDeletedFK[$foreign_key] = $this->$value;
						}

						// get primary key of related model
						if ($isManyMany) {
							$mainPK = array_keys($relation->link)[0];
							foreach ($relModel->primaryKey as $attr => $value) {
								if ($attr != $mainPK) {
									$dontDeletePk[$attr][] = $value;
								}
							}
						} else {
							$dontDeletePk[] = is_object($relModel) ? $relModel->primaryKey : $relModel;
						}
					}

					if (!$wasNewRecord) {
						// DELETE WITH 'NOT IN' PK MODEL & REL MODEL
						if ($isManyMany) {
							// Many Many
							$query = ['and', $notDeletedFK];
							foreach ($dontDeletePk as $attr => $value) {
								$notIn = ['not in', $attr, $value];
								array_push($query, $notIn);
							}
							try {
								if ($isSoftDelete) {
									$relModel->updateAll($this->_rt_softdelete, $query);
								} else {
									$relModel->deleteAll($query);
								}
							} catch (IntegrityException $exc) {
								$this->addError($rel_name, "Data can't be deleted because it's still used by another data.");
								$success = false;
							}
						} else {
							// Has Many
							$query = ['and', $notDeletedFK, ['not in', $relPKAttr[0], $dontDeletePk]];
							if (!empty($dontDeletePk)) {
								try {
									if ($isSoftDelete) {
										$relModel->updateAll($this->_rt_softdelete, $query);
									} else {
										$relModel->deleteAll($query);
									}
								} catch (IntegrityException $exc) {
									$this->addError($rel_name, "Data can't be deleted because it's still used by another data.");
									$success = false;
								}
							}
						}
					}

					foreach ($records as $index => $relModel) {
						$relSave = $relModel->save();

						if (!$relSave || !empty($relModel->errors)) {
							$relModelWords = Yii:: t('churros', Inflector::camel2words(StringHelper::basename($relModelClass)));
							$index++;
							foreach ($relModel->errors as $validation) {
								foreach ($validation as $errorMsg) {
									$this->addError($rel_name, "$relModelWords #$index : $errorMsg");
								}
							}
							$success = false;
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
							$this->addError($rel_name, "$recordsWords : $errorMsg");
						}
					}
					$success = false;
				}
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
    public function deleteWithRelated($relations = [])
    {
        /* @var $this ActiveRecord */
        $db = $this->getDb();
        $trans = $db->beginTransaction();
        $isSoftDelete = isset($this->_rt_softdelete);
        try {
            $error = false;
            $relData = $this->getRelationData($relations);
            foreach ($relData as $data) {
                $array = [];
                if ($data['ismultiple']) {
                    $link = $data['link'];
                    if (count($this->{$data['name']})) {
                        foreach ($link as $key => $value) {
                            if (isset($this->$value)) {
                                $array[$key] = $this->$value;
                            }
                        }
                        if ($isSoftDelete) {
                            $error = !$this->{$data['name']}[0]->updateAll($this->_rt_softdelete, ['and', $array]);
                        } else {
                            $error = !$this->{$data['name']}[0]->deleteAll(['and', $array]);
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
        } catch (Exception $exc) {
            $trans->rollBack();
            throw $exc;
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
            $relData = $this->getRelationData($relations);
            foreach ($relData as $data) {
                $array = [];
                if ($data['ismultiple']) {
                    $link = $data['link'];
                    if (count($this->{$data['name']})) {
                        foreach ($link as $key => $value) {
                            if (isset($this->$value)) {
                                $array[$key] = $this->$value;
                            }
                        }
                        $error = !$this->{$data['name']}[0]->updateAll($this->_rt_softrestore, ['and', $array]);
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

    public function getRelationData($relations)
    {
        $stack = [];
        $is_array = is_array($relations);
        if( !$is_array ) {
			$relations = [ $relations ];
		}
		foreach ($relations as $name) {
			/* @var $rel ActiveQuery */
			$rel = $this->getRelation($name);
			$stack[$name]['name'] = $name;
			$stack[$name]['method'] = 'get' . ucfirst($name);
			$stack[$name]['ismultiple'] = $rel->multiple;
			$stack[$name]['modelClass'] = $rel->modelClass;
			$stack[$name]['link'] = $rel->link;
			$stack[$name]['via'] = $rel->via;
		}
		if( !$is_array) {
			return $stack[$name];
		} else {
			return $stack;
		}
    }


}
