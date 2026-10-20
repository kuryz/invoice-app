<?php

namespace App\Core;

use PDO;
use RuntimeException;

abstract class Model implements \JsonSerializable
{
    protected static string $table;
    protected static string $primaryKey = 'id';

    /**
     * Database attributes
     */
    protected array $attributes = [];

    /**
     * Mass assignable attributes
     */
    protected array $fillable = [];

    /**
     * Hidden attributes
     */
    protected array $hidden = [];

    /**
     * Attribute casts
     */
    protected array $casts = [];
    protected array $relations = [];

    /**
     * Database connection
     */
    private static ?Database $db = null;

    /**
     * Constructor
     *
     * NOTE:
     * We hydrate directly from database.
     * $fillable is ONLY used for mass assignment (fill()).
     */
    public function __construct(array $attributes = [])
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }

        $this->attributes = $attributes;
    }

    // public function toArray(): array
    // {
    //     $data = $this->attributes;
    //     foreach ($this->hidden as $field) {
    //         unset($data[$field]);
    //     }
    //     return $data;
    // }
    public function toArray(): array
    {
        $data = $this->attributes;

        // Remove hidden fields
        foreach ($this->hidden as $field) {
            unset($data[$field]);
        }

        // Include loaded relationships
        foreach ($this->relations as $name => $relation) {
            if (is_array($relation)) {
                $data[$name] = array_map(
                    function ($item) {
                        if ($item instanceof \JsonSerializable) {
                            return $item->jsonSerialize();
                        }

                        return $item;
                    },
                    $relation
                );
            } elseif ($relation instanceof \JsonSerializable) {
                $data[$name] = $relation->jsonSerialize();
            } else {
                $data[$name] = $relation;
            }
        }

        return $data;
    }

    public static function fromArray(array $data): static
    {
        $model = new static();
        $model->attributes = $data;
        return $model;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Database instance
     */
    public static function getDb(): Database
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }

        return self::$db;
    }

    /**
     * Table name
     */
    public static function getTable(): string
    {
        if (!empty(static::$table)) {
            return static::$table;
        }
        $parts = explode('\\', static::class);
        return strtolower(end($parts)) . 's';
    }

    /**
     * New Query Builder
     */
    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::class);
    }

    /**
     * Magic getter
     */
    public function __get($key)
    {
        // logger()->debug('__get()', [
        //     'model' => static::class,
        //     'key'   => $key,
        // ]);
        if ($key === '' || $key === null) {
            return null;
        }
        $method = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key))) . 'Attribute';

        if (method_exists($this, $method)) {
            try {
                return $this->$method();
            } catch (\Throwable $e) {
                throw new \Exception(
                    static::class . "::{$method}() => " . $e->getMessage(),
                    0,
                    $e
                );
            }
        }

        if (array_key_exists($key, $this->attributes)) {
            return $this->getAttribute($key);
        }

        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }
        if (method_exists($this, $key)) {
            $relation = $this->$key();

            if ($relation instanceof \App\Core\Relations\Relation) {
                return $this->relations[$key] = $relation->getResults();
            }
            return $relation;
        }
        return null;
    }

    public function __isset($key): bool
    {
        if (array_key_exists($key, $this->attributes)) {
            return true;
        }
        if (array_key_exists($key, $this->relations)) {
            return true;
        }
        return method_exists($this, $key);
    }

    /**
     * Magic setter
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function setRelation(string $name, $value): self
    {
        $this->relations[$name] = $value;
        return $this;
    }

    public function getRelation(string $name)
    {
        return $this->relations[$name] ?? null;
    }

    public function relationLoaded(string $name): bool
    {
        return array_key_exists($name, $this->relations);
    }

    public function load(string|array $relations): static
    {
        $relations = is_array($relations) ? $relations : [$relations];

        foreach ($relations as $relation) {
            if (!method_exists($this, $relation)) {
                throw new \InvalidArgumentException(
                    "Relationship [{$relation}] does not exist on " . static::class
                );
            }

            $this->{$relation} = $this->{$relation}();
        }

        return $this;
    }

    /**
     * isset()
     */
    // public function __isset(string $key): bool
    // {
    //     return array_key_exists($key, $this->attributes);
    // }

    /**
     * Get all attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Fill only fillable fields
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {

            if (empty($this->fillable) || in_array($key, $this->fillable, true)) {
                $this->attributes[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Get attribute
     */
    public function getAttribute(string $key): mixed
    {
        $value = $this->attributes[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (isset($this->casts[$key])) {

            switch ($this->casts[$key]) {

                case 'integer':
                case 'int':
                    return (int) $value;

                case 'float':
                case 'double':
                    return (float) $value;
                case 'decimal':
                    return $value !== null
                        ? (string) $value
                        : null;
                case 'datetime':
                case 'date':
                    return new \Carbon\Carbon($value);

                case 'boolean':
                case 'bool':
                    return (bool) $value;

                case 'array':
                    return is_array($value)
                        ? $value
                        : json_decode($value, true);

                case 'json':
                    return json_decode($value, true);

                case 'string':
                    return (string) $value;
            }
        }

        return $value;
    }

    /**
     * Set attribute
     */
    public function setAttribute(string $key, $value): self
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Array representation
     */
    // public function toArray(): array
    // {
    //     $data = $this->attributes;

    //     foreach ($this->hidden as $field) {
    //         unset($data[$field]);
    //     }

    //     return $data;
    // }

    /**
     * First record
     */
    public static function first(): ?static
    {
        return static::query()->first();
    }

    /**
     * Find by primary key
     */
    public static function find(int|string $id): ?static
    {
        return static::where(static::$primaryKey, '=', $id)->first();
    }

    /**
     * Find or fail
     */
    public static function findOrFail(int|string $id): static
    {
        $model = static::find($id);

        if (!$model) {
            throw new RuntimeException(static::$table . ' not found.');
        }

        return $model;
    }

    public static function searchable(array $columns): QueryBuilder
    {
        return static::query()->searchable($columns);
    }

    /**
     * Get all
     */
    public static function all(array $orderBy = []): array
    {
        $query = static::query();

        if (!empty($orderBy)) {

            $query->orderBy(
                $orderBy['column'],
                $orderBy['direction'] ?? 'ASC'
            );
        }

        return $query->get();
    }

    /**
     * Where
     */
    public static function where(string $column, $operator, $value = null): QueryBuilder
    {
        $query = static::query();

        if (func_num_args() === 2) {
            return $query->where($column, $operator);
        }

        return $query->where($column, $operator, $value);
    }

    public static function orWhere(string $column, $operator, $value = null): QueryBuilder
    {
        $query = static::query();

        if (func_num_args() === 2) {
            return $query->orWhere($column, $operator);
        }

        return $query->orWhere($column, $operator, $value);
    }

    /**
     * Where First
     */
    public static function whereFirst(string $column, string $operator, $value): ?static
    {
        return static::where($column, $operator, $value)->first();
    }

    public static function whereIn(string $column, array $values): QueryBuilder
    {
        return static::query()->whereIn($column, $values);
    }

    /**
     * Create
     */
    public static function create(array $attributes): static
    {
        $model = new static();

        $model->fill($attributes);

        $model->save();

        return $model;
    }

    /**
     * Update or Create
     */
    public static function updateOrCreate(array $attributes, array $values): static
    {
        $query = static::query();

        foreach ($attributes as $column => $value) {
            $query->where($column, '=', $value);
        }

        $model = $query->first();

        if (!$model) {

            $model = new static();

            $model->fill(array_merge($attributes, $values));

            $model->save();

            return $model;
        }

        $model->fill($values);
        $model->save();
        return $model;
    }

    /**
     * Save
     */
    public function save(): bool
    {
        $now = date('Y-m-d H:i:s');
        if (!empty($this->attributes[static::$primaryKey])) {
            $this->updated_at = $now;
            return $this->performUpdate();
        }
        $this->created_at = $now;
        $this->updated_at = $now;

        return $this->performInsert();
    }

    // rel
    
    public function belongsTo($related, $foreignKey, $ownerKey = 'id')
    {
        return new \App\Core\Relations\BelongsTo(
            $this,
            $related,
            $foreignKey,
            $ownerKey
        );
    }

    protected function hasMany($related, $foreignKey, $localKey = 'id')
    {
        return new \App\Core\Relations\HasMany(
            $this,
            $related,
            $foreignKey,
            $localKey
        );
    }

    protected function hasOne($related, $foreignKey, $localKey = 'id')
    {
        return new \App\Core\Relations\HasOne(
            $this,
            $related,
            $foreignKey,
            $localKey
        );
    }

    /**
     * helper
     *  */
    protected function prepareAttributesForSave(): array
    {
        $attributes = $this->attributes;
        foreach ($attributes as $key => $value) {
            if (!isset($this->casts[$key])) {
                continue;
            }
            switch ($this->casts[$key]) {
                case 'array':
                case 'json':
                    $attributes[$key] = is_array($value)
                        ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : $value;
                    break;
                case 'object':
                    $attributes[$key] = (is_array($value) || is_object($value))
                        ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : $value;
                    break;
                case 'boolean':
                case 'bool':
                    $attributes[$key] = $value ? 1 : 0;
                    break;
                case 'datetime':
                    if ($value instanceof \DateTimeInterface) {
                        $attributes[$key] = $value->format('Y-m-d H:i:s');
                    }
                    break;
                case 'date':
                    if ($value instanceof \DateTimeInterface) {
                        $attributes[$key] = $value->format('Y-m-d');
                    }
                    break;
                case 'decimal':
                    $attributes[$key] = $value !== null
                        ? (string) $value
                        : null;
                    break;
            }
        }
        return $attributes;
    }

    /**
     * Insert
     */
    protected function performInsert(): bool
    {
        $db = self::getDb();
        $attributes = $this->prepareAttributesForSave();
        $columns = array_keys($attributes);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            static::$table,
            implode(',', $columns),
            $placeholders
        );
        $stmt = $db->prepare($sql);
        $success = $stmt->execute(array_values($attributes));
        if ($success) {
            $this->attributes[static::$primaryKey] = (int) $db->lastInsertId();
        }
        return $success;
    }

    /**
     * Update
     */
    protected function performUpdate(): bool
    {
        $db = self::getDb();
        $attributes = $this->prepareAttributesForSave();
        $id = $attributes[static::$primaryKey];
        $columns = [];
        $values = [];
        foreach ($attributes as $column => $value) {
            if ($column === static::$primaryKey) {
                continue;
            }
            $columns[] = "{$column} = ?";
            $values[] = $value;
        }
        $values[] = $id;
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = ?",
            static::$table,
            implode(', ', $columns),
            static::$primaryKey
        );
        $stmt = $db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Delete
     */
    public function delete(): bool
    {
        if (empty($this->attributes[static::$primaryKey])) {
            return false;
        }

        return static::query()
            ->where(static::$primaryKey, '=', $this->attributes[static::$primaryKey])
            ->delete();
    }

    /**
     * Paginate
     */
    // public static function paginate(
    //     int $page = 1,
    //     int $perPage = 20,
    //     array $where = []
    // ): array {

    //     $query = static::query();

    //     foreach ($where as $column => $value) {
    //         $query->where($column, '=', $value);
    //     }

    //     $total = $query->count();

    //     $query = static::query();

    //     foreach ($where as $column => $value) {
    //         $query->where($column, '=', $value);
    //     }

    //     $data = $query
    //         ->offset(($page - 1) * $perPage)
    //         ->limit($perPage)
    //         ->get();

    //     return [
    //         'data' => $data,
    //         'pagination' => [
    //             'current_page' => $page,
    //             'per_page'     => $perPage,
    //             'total'        => $total,
    //             'last_page'    => max(1, (int) ceil($total / $perPage)),
    //         ],
    //     ];
    // }
}