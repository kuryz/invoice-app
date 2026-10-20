<?php
namespace App\Core\Relations;

class HasMany extends Relation
{
    // protected $parent;
    // protected $related;
    // protected $foreignKey;
    // protected $localKey;

    // public function __construct($parent,$related,$foreignKey,$ownerKey)
    // {
    //     $this->parent=$parent;
    //     $this->related=$related;
    //     $this->foreignKey=$foreignKey;
    //     $this->localKey=$ownerKey;
    // }

    public function eagerLoad(array $models, string $name): void
    {

        if (empty($models)) {
        return;
    }
        $ids = [];
        foreach ($models as $model) {
            $ids[] = $model->{$this->localKey};
        }
        $children = $this->related::query()
            ->whereIn($this->foreignKey, $ids)
            ->get();
        $dictionary = [];
        foreach ($children as $child) {
            $dictionary[$child->{$this->foreignKey}][] = $child;
        }
        foreach ($models as $model) {
            $key = $model->{$this->localKey};
            $model->setRelation(
                $name,
                $dictionary[$key] ?? []
            );
        }
    }

    public function getResults()
    {
        return $this->related::query()
            ->where($this->foreignKey, $this->parent->{$this->localKey})
            ->get();
    }
}