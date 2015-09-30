<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace Infuse\Model;

use Infuse\Model;
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
     * @var \Infuse\Model
     */
    protected $model;

    /**
     * @param \Infuse\Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Gets the model for this event.
     *
     * @return \Infuse\Model
     */
    public function getModel()
    {
        return $this->model;
    }
}
