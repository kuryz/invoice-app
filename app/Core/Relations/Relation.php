<?php

namespace App\Core\Relations;

use App\Core\QueryBuilder;

abstract class Relation
{
    protected $parent;
    protected string $related;
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(
        $parent,
        string $related,
        string $foreignKey,
        string $localKey
    ) {
        $this->parent = $parent;
        $this->related = $related;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    abstract public function eagerLoad(array $models, string $name): void;

    abstract public function getResults();

    public function getRelatedQuery(): QueryBuilder
    {
        return $this->related::query();
    }

    public function getTable(): string
    {
        return (new $this->related)->getTable();
    }

    public function getQualifiedForeignKey(): string
    {
        return $this->getTable() . '.' . $this->localKey;
    }

    public function getQualifiedLocalKey(): string
    {
        return $this->parent->getTable() . '.' . $this->foreignKey;
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function getLocalKey(): string
    {
        return $this->localKey;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getRelatedModel(): string
    {
        return $this->related;
    }
}