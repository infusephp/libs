<?php

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
     * @var array
     */
    protected $data;

    /**
     * @param \infuse\Model $model
     */
    public function __construct(Model $model, $data = [])
    {
        $this->model = $model;
        $this->data = $data;
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

    /**
     * Gets the data associated with this event.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
