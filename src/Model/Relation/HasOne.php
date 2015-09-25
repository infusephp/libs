<?php

namespace infuse\Model\Relation;

class HasOne extends Relation
{
    protected function initQuery()
    {
        $localKey = $this->localKey;

        $this->query->where([$this->foreignKey => $this->relation->$localKey])
                    ->limit(1);
    }

    public function getResults()
    {
        return $this->query->first();
    }
}
