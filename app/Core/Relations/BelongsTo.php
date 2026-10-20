<?php
namespace App\Core\Relations;

class BelongsTo extends Relation
{
    // protected $parent;
    // protected $related;
    // protected $foreignKey;
    // protected $localKey;

    // public function __construct($parent,$related,$foreignKey,$localKey)
    // {
    //     $this->parent=$parent;
    //     $this->related=$related;
    //     $this->foreignKey=$foreignKey;
    //     $this->localKey=$ownerKey;
    // }

    public function eagerLoad(array $models,string $name):void
    {
        $ids=[];

        foreach($models as $model){

            $value=$model->{$this->foreignKey};

            if($value){
                $ids[$value]=$value;
            }
        }

        if(empty($ids)){
            return;
        }

        $related=$this->related::query()
            ->whereIn($this->localKey,array_values($ids))
            ->get();

        $dictionary=[];

        foreach($related as $item){
            $dictionary[$item->{$this->localKey}]=$item;
        }

        foreach($models as $model){

            $id=$model->{$this->foreignKey};

            // $model->$name=$dictionary[$id] ?? null;
            // $model->setRelation($relation, $dictionary[$id] ?? null);
            $model->setRelation($name, $dictionary[$id] ?? null);
        }
    }

    public function getResults()
    {
        return $this->related::query()
            ->where($this->localKey, $this->parent->{$this->foreignKey})
            ->first();
    }
}