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


}
