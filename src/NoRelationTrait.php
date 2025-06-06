<?php

/**
 * NoRelationTrait
 *
 * @author Santilín <software@noviolento.es>
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

trait NoRelationTrait
{
    /**
     * Load all attributes including related attributes
     * @param $post
     * @param array $relations_in_form
     * @return bool
     */
    public function loadAll(array $post, array $relations_in_form = [], ?string $formName = null): bool
    {
		return $this->load($post, $formName);
    }

    /**
     * Save model including all related models already loaded
     * @param array $relations The relations to consider for this form
     * @return bool
     * @throws Exception
     */
    public function saveAll(bool $runValidation = true): bool
    {
		return $this->save($runValidation);
    }

    public function usedInRelation(string $rel_name): int
    {
		return false;
    }


    /**
     * Delete model row with all related records
     * @param array $relations The relations to consider for this form
     * @return bool
     * @throws Exception
     */
    public function deleteWithRelated(array $relations = []): bool
    {
		return $this->delete();
	}

	public function linkDetails($detail, $relation_name): void
    {
    }


}
