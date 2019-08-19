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
use \yii\db\ActiveRecord;
use \yii\db\Exception;
use yii\db\IntegrityException;
use \yii\helpers\Inflector;
use \yii\helpers\StringHelper;
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
     * Load all attribute including related attribute
     * @param $POST
     * @param array $relations
     * @return bool
     */
    public function loadAll($POST, $formName = null)
    {
        if( $formName === null ) {
			$formName = $this->formName();
		}
        if ($this->load($POST, $formName)) {
			// Look for the relations included in this form
			if (isset($POST['_form_relations']) ) {
				$relations_in_form = explode(",", $POST['_form_relations']);
			} else {
				$relations_in_form = [];
			}
            $relations_in_model = $this->getRelationData($relations_in_form);
			// Look for arrays of relations data in the POST
            foreach ($POST as $post_variable => $post_data) {
                if (is_array($post_data)) {
                    if ($post_variable == $formName) { // Main form
						// Look for embedded relations data in the main form
                        foreach ($post_data as $relName => $relAttributes) {
                            if (is_array($relAttributes) && array_key_exists($relName, $relations_in_model) ) {
                                $this->loadToRelation($relations_in_model[$relName], $relName, $relAttributes);
                            }
                        }
                    } else {
						throw new \Exception("Many to many relation");
                        $isHasMany = is_array($post_data) && is_array(current($post_data));
                        $relName = ($isHasMany) ? lcfirst(Inflector::pluralize($relation)) : lcfirst($relation);
                        if (!array_key_exists($relName, $relations_in_model)) {
                            continue;
                        }
                        $this->loadToRelation($isHasMany, $relName, $post_data);
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
     * @param $relName
     * @param $v form values
     * @return bool
     */
    private function loadToRelation($relation, $relName, $v)
    {
        /* @var $this ActiveRecord */
        /* @var $relObj ActiveRecord */
        /* @var $relModelClass ActiveRecord */
		$relModelClass = $relation['modelClass'];
        $relPKAttr = $relModelClass::primaryKey();

		if (count($relPKAttr) > 1) { // Many to many
            $container = [];
            foreach ($v as $relPost) {
                if (is_array($relPost) ) {
					// many2many relation with post = array of records
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
					// many2many relation with post = array of related ids
// 					$relName = $relName . Inflector::camel2words(StringHelper::basename($AQ->primaryModel::className()));
// 					$relModelClass = $AQ->modelClass;
// 					$relPKAttr = $relModelClass::primaryKey();
// 					echo "<pre>"; print_r($relName); echo "</pre>";
// 					echo "<pre>"; print_r($relModelClass); echo "</pre>";
// 					echo "<pre>"; print_r($relPKAttr); echo "</pre>"; die;
					$condition = [];
					$this_pk =  str_replace('%','',str_replace('{{','',str_replace('}}','',$this->tablename()))) . "_id";
					foreach( $relPKAttr as $pk ) {
						if( $pk == $this_pk ) {
							$condition[$pk] = $this->primaryKey;
						} else {
							$condition[$pk] = intval($relPost);
						}
					}
					if( $this->primaryKey ) {
						$relObj = $relModelClass::findOne($condition);
						if (is_null($relObj)) {
							$relObj = new $relModelClass;
						}
					} else {
						$relObj = new $relModelClass;
					}
					$relObj->setAttributes($condition, false);
//  					echo "<pre>"; print_r($relObj); echo "</pre>"; die;
					$container[] = $relObj;
                }
            }
            $this->populateRelation($relName, $container);
        } else if ($relation['via'] == null) { // Has Many
            $container = [];
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
            $this->populateRelation($relName, $container);
        } else {
            $relObj = (empty($v[$relPKAttr[0]])) ? new $relModelClass : $relModelClass::findOne($v[$relPKAttr[0]]);
            $relObj->load($v, '');
            $this->populateRelation($relName, $relObj);
        }
        return true;
    }

    /**
     * Save model including all related models already loaded
     * @param array $relations The relations to consider for this form
     * @return bool
     * @throws Exception
     */
    public function saveAll($relations = [])
    {
        /* @var $this ActiveRecord */
        $db = $this->getDb();
        $trans = $db->beginTransaction();
        if( $relations == [] ) {
			if (isset($POST['_form_relations']) ) {
				$relations = explode(",", $POST['_form_relations']);
			}
		}
        try {
            if ($this->save()) {
                $error = false;
				foreach ($this->relatedRecords as $rel_name => $records) {
				/* @var $records ActiveRecord | ActiveRecord[] */
					if (!empty($records)) {
						$justUpdateIds = !$records[0] instanceof \yii\db\BaseActiveRecord;
						if( $justUpdateIds ) {
							$this->updateIds($rel_name, $records);
						} else {
							$this->updateRecords($rel_name, $records);
						}
					}
				}
//                 // Remove remaining children
//                 $relAvail = array_keys($this->relatedRecords);
//                 $relData = $this->getRelationData($relations);
//                 $allRel = array_keys($relData);
//                 $noChildren = array_diff($allRel, $relAvail);
//
//                 foreach ($noChildren as $relName) {
//                     /* @var $relModel ActiveRecord */
//                     if (empty($relData[$relName]['via']) ) {
//                         $relModel = new $relData[$relName]['modelClass'];
//                         $condition = [];
//                         $isManyMany = count($relModel->primaryKey()) > 1;
//                         if ($isManyMany) {
//                             foreach ($relData[$relName]['link'] as $k => $v) {
//                                 $condition[$k] = $this->$v;
//                             }
//                             try {
//                                 if ($isSoftDelete) {
//                                     $relModel->updateAll($this->_rt_softdelete, ['and', $condition]);
//                                 } else {
//                                     $relModel->deleteAll(['and', $condition]);
//                                 }
//                             } catch (IntegrityException $exc) {
//                                 $this->addError($relData[$relName]['name'], Yii::t('churros', "Data can't be deleted because it's still used by another data."));
//                                 $error = true;
//                             }
//                         } else {
//                             if ($relData[$relName]['ismultiple']) {
//                                 foreach ($relData[$relName]['link'] as $k => $v) {
//                                     $condition[$k] = $this->$v;
//                                 }
//                                 try {
//                                     if ($isSoftDelete) {
//                                         $relModel->updateAll($this->_rt_softdelete, ['and', $condition]);
//                                     } else {
//                                         $relModel->deleteAll(['and', $condition]);
//                                     }
//                                 } catch (IntegrityException $exc) {
//                                     $this->addError($relData[$relName]['name'], Yii::t('churros', "Data can't be deleted because it's still used by another data."));
//                                     $error = true;
//                                 }
//                             }
//                         }
//                     }
//                 }


                if ($error) {
                    $trans->rollback();
                    $this->isNewRecord = $isNewRecord;
                    return false;
                }
                $trans->commit();
                return true;
            } else {
                return false;
            }
        } catch (Exception $exc) {
            $trans->rollBack();
            $this->isNewRecord = $isNewRecord;
            throw $exc;
        }
    }

    private function updateIds($relName, $records)
    {
		$relation = $this->getRelation($relName);
        $isNewRecord = $this->isNewRecord;
        $isSoftDelete = isset($this->_rt_softdelete);
		$notDeletedPK = [];
		$notDeletedFK = [];
		if ($relation->multiple) {
			$relModelClass = $relation->modelClass;
			$relPKAttr = $relModelClass::primarykey();
			$isManyMany = (count($relPKAttr) > 1);
			if (!$isNewRecord) {
				// Delete records not in the form post data
				$relModels = [];
				foreach ($records as $pk_name => $pk_value) {
					// Set relModel foreign key
					$relModels[] = $relModel = new $relModelClass;
					foreach ($relation->link as $foreign_key => $value) {
						$relModel->$foreign_key = $this->$value;
						$notDeletedFK[$foreign_key] = $this->$value;
					}
					// get primary key of related model
					if ($isManyMany) {
						$mainPK = array_keys($relation->link)[0];
						foreach ($relModel->primaryKey as $attr => $value) {
							if ($attr != $mainPK) {
								$notDeletedPK[$attr][] = $value;
							}
						}
					} else {
						$notDeletedPK[] = $pk_value;
					}
				}
				// DELETE WITH 'NOT IN' PK MODEL & REL MODEL
				if ($isManyMany) {
					// Many Many
					$query = ['and', $notDeletedFK];
					foreach ($notDeletedPK as $attr => $value) {
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
						$error = true;
					}
				} else {
					// Has Many
					if (!empty($notDeletedPK)) {
						$query = ['and', $notDeletedFK, ['not in', $relPKAttr[0], $notDeletedPK]];
						try {
							if ($isSoftDelete) {
								$relModel->updateAll($this->_rt_softdelete, $query);
							} else {
								$relModel->deleteAll($query);
							}
						} catch (IntegrityException $exc) {
							$this->addError($rel_name, "Data can't be deleted because it's still used by another data.");
							$error = true;
						}
					}
				}
			}
// 			// Save ids
// 			foreach ($records as $index => $relModel) {
// 				$relSave = $relModel->save();
//
// 				if (!$relSave || !empty($relModel->errors)) {
// 					$relModelWords = Yii::t('churros', Inflector::camel2words(StringHelper::basename($AQ->modelClass)));
// 					$index++;
// 					foreach ($relModel->errors as $validation) {
// 						foreach ($validation as $errorMsg) {
// 							$this->addError($rel_name, "$relModelWords #$index : $errorMsg");
// 						}
// 					}
// 					$error = true;
// 				}
// 			}
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
    }

    private function updateRecords($relName, $records)
    {
		$error = false;
		$relation = $this->getRelation($relName);
        $isNewRecord = $this->isNewRecord;
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
						$relModelWords = Yii::t('churros', Inflector::camel2words(StringHelper::basename($AQ->modelClass)));
						$index++;
						foreach ($relModel->errors as $validation) {
							foreach ($validation as $errorMsg) {
								$this->addError($rel_name, "$relModelWords #$index : $errorMsg");
							}
						}
						$error = true;
					} else {
					$relName = $relName . Inflector::camel2words(StringHelper::basename($AQ->primaryModel::className()));
						$records[$index] = new $relName;
						$relName->$link = $relModel->primaryKey;
					}
				}
			}
		}
		if( !$error ) {
			$notDeletedPK = [];
			$notDeletedFK = [];
			if ($relation->multiple) {
				$relModelClass = $relation->modelClass;
				$relPKAttr = $relModelClass::primarykey();
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
								$notDeletedPK[$attr][] = $value;
							}
						}
					} else {
						$notDeletedPK[] = is_object($relModel) ? $relModel->primaryKey : $relModel;
					}
				}

				if (!$isNewRecord) {
					// DELETE WITH 'NOT IN' PK MODEL & REL MODEL
					if ($isManyMany) {
						// Many Many
						$query = ['and', $notDeletedFK];
						foreach ($notDeletedPK as $attr => $value) {
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
							$error = true;
						}
					} else {
						// Has Many
						$query = ['and', $notDeletedFK, ['not in', $relPKAttr[0], $notDeletedPK]];
						if (!empty($notDeletedPK)) {
							try {
								if ($isSoftDelete) {
									$relModel->updateAll($this->_rt_softdelete, $query);
								} else {
									$relModel->deleteAll($query);
								}
							} catch (IntegrityException $exc) {
								$this->addError($rel_name, "Data can't be deleted because it's still used by another data.");
								$error = true;
							}
						}
					}
				}

				foreach ($records as $index => $relModel) {
					$relSave = $relModel->save();

					if (!$relSave || !empty($relModel->errors)) {
						$relModelWords = Yii::t('churros', Inflector::camel2words(StringHelper::basename($AQ->modelClass)));
						$index++;
						foreach ($relModel->errors as $validation) {
							foreach ($validation as $errorMsg) {
								$this->addError($rel_name, "$relModelWords #$index : $errorMsg");
							}
						}
						$error = true;
					}
				}
			} else {
				//Has One
				foreach ($link as $key => $value) {
					$records->$key = $this->$value;
				}
				$relSave = $records->save();
				if (!$relSave || !empty($records->errors)) {
					$recordsWords = Yii::t('churros', Inflector::camel2words(StringHelper::basename($AQ->modelClass)));
					foreach ($records->errors as $validation) {
						foreach ($validation as $errorMsg) {
							$this->addError($rel_name, "$recordsWords : $errorMsg");
						}
					}
					$error = true;
				}
			}
		}

    }

    /**
     * Deleted model row with all related records
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
        if (is_array($relations)) {
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
		}
/*
        } else {
            $ARMethods = get_class_methods('\yii\db\ActiveRecord');
            $modelMethods = get_class_methods('\yii\base\Model');
            $reflection = new \ReflectionClass($this);
            /* @var $method \ReflectionMethod
            foreach ($reflection->getMethods() as $method) {
                if (in_array($method->name, $ARMethods) || in_array($method->name, $modelMethods)) {
                    continue;
                }
                if ($method->name === 'getRelationData') {
                    continue;
                }
                if ($method->name === 'getAttributesWithRelated') {
                    continue;
                }
                if (strpos($method->name, 'get') !== 0) {
                    continue;
                }
                if($method->getNumberOfParameters() > 0) {
                    continue;
                }
                try {
                    $rel = call_user_func(array($this, $method->name));
                    if ($rel instanceof ActiveQuery) {
                        $name = lcfirst(preg_replace('/^get/', '', $method->name));
                        $stack[$name]['name'] = lcfirst(preg_replace('/^get/', '', $method->name));
                        $stack[$name]['method'] = $method->name;
                        $stack[$name]['ismultiple'] = $rel->multiple;
                        $stack[$name]['modelClass'] = $rel->modelClass;
                        $stack[$name]['link'] = $rel->link;
                        $stack[$name]['via'] = $rel->via;
                    }
                } catch (\Exception $exc) {
                    //if method name can't be called,
                }
            }
        }
        */
        return $stack;
    }

    /**
     * return array like this
     * Array
     * (
     *      [attr1] => value1
     *      [attr2] => value2
     *      [relationName] => Array
     *          (
     *              [0] => Array
     *                  (
     *                      [attr1] => value1
     *                      [attr2] => value2
     *                  )
     *          )
     *  )
     * @return array
     */
    public function getAttributesWithRelated()
    {
        /* @var $this ActiveRecord */
        $return = $this->attributes;
        foreach ($this->relatedRecords as $name => $records) {
            $AQ = $this->getRelation($name);
            if ($AQ->multiple) {
                foreach ($records as $index => $record) {
                    $return[$name][$index] = $record->attributes;
                }
            } else {
                $return[$name] = $records->attributes;
            }
        }
        return $return;
    }

    /**
     * TranslationTrait manages methods for all translations used in Krajee extensions
     *
     * @author Kartik Visweswaran <kartikv2@gmail.com>
     * @since 1.8.8
     * Yii i18n messages configuration for generating translations
     * source : https://github.com/kartik-v/yii2-krajee-base/blob/master/TranslationTrait.php
     * Edited by : Yohanes Candrajaya <moo.tensai@gmail.com>
     *
     *
     * @return void
     */
    public function initI18N()
    {
        $reflector = new \ReflectionClass(get_class($this));
        $dir = dirname($reflector->getFileName());

        Yii::setAlias("@churros", $dir);
        $config = [
            'class' => 'yii\i18n\PhpMessageSource',
            'basePath' => "@churros/messages",
            'forceTranslation' => true
        ];
        $globalConfig = ArrayHelper::getValue(Yii::$app->i18n->translations, "churros*", []);
        if (!empty($globalConfig)) {
            $config = array_merge($config, is_array($globalConfig) ? $globalConfig : (array)$globalConfig);
        }
        Yii::$app->i18n->translations["churros*"] = $config;
    }
}
