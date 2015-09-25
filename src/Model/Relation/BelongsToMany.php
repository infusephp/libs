<?php

namespace infuse\Model\Relation;

class BelongsToMany extends Relation
{
    protected function initQuery()
    {
        $localKey = $this->localKey;

        $this->query->where([$this->foreignKey => $this->relation->$localKey]);
    }

    public function getResults()
    {
        return $this->query->execute();
    }
}
