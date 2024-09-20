<?php
/**
 * @link
 * @copyright
 * @license
 */

namespace santilin\churros\json;

use yii\helpers\ArrayHelper;
use yii\data\ArrayDataProvider;

class JsonDataProvider extends ArrayDataProvider
{

    private function sortArrayByKey(&$array, $key, $direction = SORT_ASC )
    {
        usort($array, function($a, $b) use ($key, $direction) {
            if (!isset($a[$key]) || !isset($b[$key])) {
                return 0; // If key does not exist in either array, consider them equal
            }

            if ($direction === SORT_ASC) {
                return $a[$key] <=> $b[$key]; // Ascending order
            } else {
                return $b[$key] <=> $a[$key]; // Descending order
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareKeys($models)
    {
        if ($this->key !== null) {
            $keys = [];
            foreach ($models as $model) {
                if (is_string($this->key)) {
					if (is_string($model)) {
						$keys[] = $model;
					} else {
						$keys[] = $model[$this->key];
					}
                } else {
                    $keys[] = call_user_func($this->key, $model);
                }
            }

            return $keys;
        }

        return array_keys($models);
    }

    /**
     * {@inheritdoc}
     */
    public function setModels($models)
    {
        if (!empty($this->sort->defaultOrder)) {
            foreach ($this->sort->defaultOrder as $attr => $dir) {
                $this->sortArrayByKey($models, $attr, $dir);
                break;
            }
        }
        parent::setModels($models);
    }


}
