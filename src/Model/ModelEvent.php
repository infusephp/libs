<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace infuse\Model;

use infuse\Model;
use Symfony\Component\EventDispatcher\Event;

class ModelEvent extends Event
{
    const CREATING = 'model.creating';
    const CREATED = 'model.created';
    const UPDATING = 'model.updating';
    const UPDATED = 'model.updated';
    const DELETING = 'model.deleting';
    const DELETED = 'model.deleted';

    /**
     * @var \infuse\Model
     */
    protected $model;

    /**
     * @param \infuse\Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Gets the model for this event.
     *
     * @return \infuse\Model
     */
    public function getModel()
    {
        return $this->model;
    }
}
