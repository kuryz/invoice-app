<?php

namespace App\Core\Relations;

class HasOne extends Relation
{
    // protected $parent;
    // protected string $related;
    // protected string $foreignKey;
    // protected string $localKey;

    // public function __construct(
    //     $parent,
    //     string $related,
    //     string $foreignKey,
    //     string $localKey = 'id'
    // ) {
    //     $this->parent = $parent;
    //     $this->related = $related;
    //     $this->foreignKey = $foreignKey;
    //     $this->localKey = $localKey;
    // }

    public function eagerLoad(array $models, string $relation): void
    {
        $ids = [];

        foreach ($models as $model) {
            $ids[] = $model->{$this->localKey};
        }

        $ids = array_unique(array_filter($ids));

        if (empty($ids)) {
            return;
        }

        $children = $this->related::query()
            ->whereIn($this->foreignKey, $ids)
            ->get();

        $dictionary = [];

        foreach ($children as $child) {
            $dictionary[$child->{$this->foreignKey}] = $child;
        }

        foreach ($models as $model) {
            $key = $model->{$this->localKey};

            // $model->{$relation} = $dictionary[$key] ?? null;
            $model->setRelation($name, $dictionary[$key] ?? null);
        }
    }

    public function getResults()
    {
        return $this->related::query()
            ->where($this->foreignKey, $this->parent->{$this->localKey})
            ->first();
    }
}