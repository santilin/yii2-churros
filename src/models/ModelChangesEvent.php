<?php

/*
 * This file is part of the 2amigos/yii2-usuario project.
 *
 * (c) 2amigOS! <http://2amigos.us/>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace santilin\churros\models;

use yii\base\Event;
use yii\db\ActiveRecord;

/**
 * @property-read User $user
 */
class ModelChangesEvent extends Event
{
    const EVENT_CHANGES_SAVED = 'changesSaved';

    protected $changes_record;

    public function __construct(ActiveRecord $changes_record, array $config = [])
    {
        $this->changes_record = $changes_record;
        parent::__construct($config);
    }

    public function getChangesRecord()
    {
        return $this->changes_record;
    }
}
