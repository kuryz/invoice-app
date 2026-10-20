<?php

namespace App\Core;
use Closure;

use PDO;

class QueryBuilder
{
    protected string $model;
    protected array $wheres = [];
    protected ?string $orderBy = null;
    protected ?string $groupBy = null;
    protected ?int $limitValue = null;
    protected ?int $offset = null;
    protected array $whereExists = [];
    protected array $with = [];
    protected array $searchColumns = [];
    protected ?string $searchValue = null;
    protected string $select = '*';
    protected array $selects = ['*'];
    protected bool $distinct = false;
    protected bool $lockForUpdate = false;

    public function __construct(string $model)
    {
        $this->model = $model;
    }

    public function __call($method, $arguments)
    {
        $model = new $this->model;

        if (method_exists($model, $method)) {
            array_unshift($arguments, $this);

            $model->$method(...$arguments);

            return $this;
        }

        throw new \BadMethodCallException("Method {$method} does not exist.");
    }

    /**
     * WHERE
     */

     public function where($column, $operator = null, $value = null): self
     {
         // Support grouped where
         if ($column instanceof Closure) {
             $query = new static($this->model);
     
             $column($query);
     
             $this->wheres[] = [
                 'type'  => 'AND',
                 'group' => true,
                 'items' => $query->wheres,
             ];
     
             return $this;
         }
     
         // where('column', value)
         if (func_num_args() === 2) {
             $value = $operator;
             $operator = '=';
         }
     
         $this->wheres[] = [
             'type'      => 'AND',
             'column'    => $column,
             'operator'  => strtoupper($operator),
             'value'     => $value,
             'is_array'  => false,
             'raw'       => false,
         ];
     
         return $this;
     }

    /**
     * OR WHERE
     */
    public function orWhere($column, $operator = null, $value = null): self
    {
        if ($column instanceof Closure) {
            $query = new static($this->model);
            $column($query);
            $this->wheres[] = [
                'type'  => 'OR',
                'group' => true,
                'items' => $query->wheres,
            ];
            return $this;
        }
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheres[] = [
            'type'      => 'OR',
            'column'    => $column,
            'operator'  => strtoupper($operator),
            'value'     => $value,
            'is_array'  => false,
            'raw'       => false,
        ];
        return $this;
    }

    /**
     * WHERE IN
     */
    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            return $this;
        }

        $this->wheres[] = [
            'type'      => 'AND',
            'column'    => $column,
            'operator'  => 'IN',
            'value'     => $values,
            'is_array'  => true,
        ];

        return $this;
    }

    /**
     * WHERE NOT IN
     */
    public function whereNotIn(string $column, array $values): self
    {
        if (empty($values)) {
            return $this;
        }

        $this->wheres[] = [
            'type'      => 'AND',
            'column'    => $column,
            'operator'  => 'NOT IN',
            'value'     => $values,
            'is_array'  => true,
        ];

        return $this;
    }

    public function whereExists(callable $callback): self
    {
        $this->whereExists[] = [
            'type' => 'AND',
            'callback' => $callback,
        ];
        return $this;
    }

    public function whereLike(string $column, string $value): self
    {
        return $this->where($column, 'LIKE', $value);
    }

    public function orWhereLike(string $column, string $value): self
    {
        return $this->orWhere($column, 'LIKE', $value);
    }

    public function whereHas(string $relation, callable $callback): self
    {
        return $this->addWhereHas('AND', $relation, $callback);
    }

    public function orWhereHas(string $relation, callable $callback): self
    {
        return $this->addWhereHas('OR', $relation, $callback);
    }

    protected function addWhereHas(string $boolean, string $relation, callable $callback): self
    {
        $model = new $this->model;

        /** @var \App\Core\Relations\Relation $rel */
        $rel = $model->$relation();

        $related = $rel->getRelatedQuery();

        $callback($related);

        $this->wheres[] = [
            'type'     => $boolean,
            'relation' => true,
            'relationObject' => $rel,
            'query'    => $related,
        ];

        return $this;
    }

    // lock
    public function lockForUpdate(): self
    {
        $this->lockForUpdate = true;

        return $this;
    }

    // with
    public function with($relations)
    {
        if (is_string($relations)) {
            $relations = [$relations];
        }
        $this->with = array_merge($this->with, $relations);
        return $this;
    }
    // searchable
    public function searchable(array $columns, ?string $search = null): self
    {
        $search = trim($search ?? request()->input('search', ''));
        if ($search === '') {
            return $this;
        }
        $this->where(function ($q) use ($columns, $search) {
            foreach ($columns as $index => $column) {
                // relationship search
                if (strpos($column, ':') !== false) {
                    [$relation, $field] = explode(':', $column, 2);
                    $method = $index === 0 ? 'whereHas' : 'orWhereHas';
                    $q->$method($relation, function ($rel) use ($field, $search) {
                        $rel->where($field, 'LIKE', "%{$search}%");
                    });
                } else {
                    if ($index === 0) {
                        $q->where($column, 'LIKE', "%{$search}%");
                    } else {
                        $q->orWhere($column, 'LIKE', "%{$search}%");
                    }
                }
            }
        });
        return $this;
    }

    protected function eagerLoad(array $models): void
    {
        if (empty($models) || empty($this->with)) {
            return;
        }
        foreach ($this->with as $relation) {
            $parts = explode('.', $relation);
            $this->eagerLoadRelation($models, $parts);
        }
    }

    protected function eagerLoadRelation(array $models, array $parts): void
    {
        $relation = array_shift($parts);

        if (!$relation) {
            return;
        }
        $first = reset($models);
        if (!$first || !method_exists($first, $relation)) {
            return;
        }
        $reflection = $first->$relation();
        if (!$reflection instanceof \App\Core\Relations\Relation) {
            return;
        }
        $reflection->eagerLoad($models, $relation);
        if (!empty($parts)) {
            $children = [];
            foreach ($models as $model) {
                // $loaded = $model->$relation ?? null;
                $loaded = $model->getRelation($relation);

                if ($loaded === null) {
                    $loaded = $model->$relation;
                }
                if (is_array($loaded)) {
                    foreach ($loaded as $item) {
                        $children[] = $item;
                    }
                } elseif ($loaded) {
                    $children[] = $loaded;
                }
            }
            $this->eagerLoadRelation($children, $parts);
        }
    }

    /**
     * ORDER BY
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);

        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $this->orderBy = "{$column} {$direction}";

        return $this;
    }

    public function groupBy(string|array $columns): self
    {
        if (is_array($columns)) {
            $columns = implode(', ', $columns);
        }

        $this->groupBy = $columns;

        return $this;
    }

    /**
     * LIMIT
     */
    public function limit(int $limit): self
    {
        $this->limitValue = $limit;

        return $this;
    }

    /**
     * OFFSET
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * SELECT
     */
    public function select(...$columns): self
    {
        if (empty($columns)) {
            $this->select = '*';
        } elseif (count($columns) === 1 && is_array($columns[0])) {
            $this->select = implode(', ', $columns[0]);
        } else {
            $this->select = implode(', ', $columns);
        }
        return $this;
    }

    public function selectRaw(string $expression): self
    {
        $this->select = $expression;

        return $this;
    }

    public function select2(...$columns): self
    {
        if (count($columns) == 1 && is_array($columns[0])) {
            $columns = $columns[0];
        }

        $this->selects = $columns;

        return $this;
    }

    public function distinct(bool $value = true): self
    {
        $this->distinct = $value;

        return $this;
    }

    // filter
    public function filter(array $fields): self
    {
        foreach ($fields as $field) {

            $value = request()->input($field);

            if ($value === null || $value === '') {
                continue;
            }

            $this->where($field, $value);
        }

        return $this;
    }

    public function dateFilter(string $column = 'created_at'): self
    {
        if(request()->input('date_from') && request()->input('date_to')){
            $from = request()->input('date_from');
            $to   = request()->input('date_to');
        }elseif(request()->input('date')){
            $date = request()->input('date');
            if (!$date) {
                return $this;
            }
            $parts = explode(' - ', $date);
            $from = $parts[0] ?? null;
            $to   = $parts[1] ?? null;
            if ($from) {
                $from = date('Y-m-d', strtotime($from));
            }
            if ($to) {
                $to = date('Y-m-d', strtotime($to));
            }
            
            // print_r($date);die;
        }
        
        
        if (!empty($from) && !empty($to)) {
            return $this->whereBetween($column, [
                $from . ' 00:00:00',
                $to . ' 23:59:59',
            ]);
        }
        if (!empty($from)) {
            return $this->where($column, '>=', $from . ' 00:00:00');
        }
        if (!empty($to)) {
            return $this->where($column, '<=', $to . ' 23:59:59');
        }
        return $this;
    }

    public function whereBetween(string $column, array $values): self
    {
        $this->wheres[] = [
            'type'      => 'AND',
            'column'    => $column,
            'operator'  => 'BETWEEN',
            'value'     => $values,
            'between'   => true,
            'raw'       => false,
        ];

        return $this;
    }

    /**
     * SUM
     */
    public function sum(string $column): float
    {
        $db = $this->model::getDb();
        $table = $this->model::getTable();

        $params = [];

        $sql = "SELECT SUM($column) AS total FROM {$table}";

        $sql .= $this->buildWhereClause($params);

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return (float)$stmt->fetchColumn();
    }

    /**
     * COUNT
     */
    public function count(): int
    {
        $db = $this->model::getDb();
        $table = $this->model::getTable();

        $params = [];
        $sql = "SELECT COUNT(*) AS total FROM {$table}";
        $sql .= $this->buildWhereClause($params);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * FIRST
     */
    public function first(): ?object
    {
        $this->limit(1);

        $results = $this->get();

        return $results[0] ?? null;
    }

    /**
     * GET
     */
    public function get(): array
    {
        $db = $this->model::getDb();
        $table = $this->model::getTable();
        $params = [];
        $select = $this->select ?: '*';
        $sql = "SELECT {$select} FROM {$table}";

        $sql .= $this->buildWhereClause($params);

        if ($this->orderBy) {
            $sql .= " ORDER BY {$this->orderBy}";
        }
        if ($this->limitValue !== null) {
            $sql .= " LIMIT {$this->limitValue}";
        }
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }
        // MySQL locking clause must come after LIMIT/OFFSET.
        if ($this->lockForUpdate) {
            $sql .= " FOR UPDATE";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = [];
        foreach ($rows as $row) {
            $results[] = new $this->model($row);
        }
        $this->eagerLoad($results);
        return $results;
    }

    /**
     * PLUCK
     */
    public function pluck(string $column): array
    {
        $this->select($column);

        $rows = $this->get();

        $values = [];

        foreach ($rows as $row) {
            $values[] = $row->$column;
        }

        return $values;
    }

    /**
     * EXISTS
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function groupSumByDate(string $type, int $days = 30, string $dateColumn = 'created_at', string $amountColumn = 'amount', string $typeColumn = 'trx_type'): array
    {
        $db = $this->model::getDb();
        $table = $this->model::getTable();
    
        $sql = "
            SELECT
                DATE($dateColumn) AS trx_date,
                SUM($amountColumn) AS total
            FROM {$table}
            WHERE {$typeColumn} = ?
              AND {$dateColumn} >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE($dateColumn)
            ORDER BY DATE($dateColumn)
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $type,
            $days
        ]);
    
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function groupSumByMonth(string $statusColumn, mixed $statusValue, int $months = 12, string $dateColumn = 'created_at', string $amountColumn = 'amount'): array
    {
        $db = $this->model::getDb();
        $table = $this->model::getTable();
        $sql = "
            SELECT
                DATE_FORMAT(MIN($dateColumn), '%M-%Y') AS month,
                SUM($amountColumn) AS total
            FROM {$table}
            WHERE {$statusColumn} = ?
            AND {$dateColumn} >= DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY YEAR($dateColumn), MONTH($dateColumn)
            ORDER BY YEAR($dateColumn), MONTH($dateColumn)
        ";
    
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $statusValue,
            $months
        ]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * SQL WHERE BUILDER
     */
    protected function buildWhereClause(array &$params): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        return ' WHERE ' . $this->compileWhere($this->wheres, $params);
    }

    protected function buildWhere(array &$params): string
    {
        return $this->compileWhere($this->wheres, $params);
    }

    protected function compileWhere(array $wheres, array &$params): string
    {
        $sql = '';
        foreach ($wheres as $index => $where) {
            if ($index > 0) {
                $sql .= ' ' . $where['type'] . ' ';
            }
            // Nested group
            if (!empty($where['group'])) {
                $sql .= '(' . $this->compileWhere($where['items'], $params) . ')';
                continue;
            }

            // --------------------------
            // whereHas / EXISTS
            // --------------------------
            if (!empty($where['relation'])) {
                $relation = $where['relationObject'];
                $subQuery = $where['query'];
                $subParams = [];
                $subWhere = $subQuery->buildWhere($subParams);
                // Remove the leading "WHERE "
                // if ($subWhere !== '') {
                //     $subWhere = preg_replace('/^\s*WHERE\s+/i', 'AND ', $subWhere);
                // }
                $sql .= "EXISTS (
                    SELECT 1
                    FROM {$relation->getTable()}
                    WHERE {$relation->getQualifiedForeignKey()}
                        = {$relation->getQualifiedLocalKey()}
                    " . ($subWhere ? " AND {$subWhere}" : "") . "
                )";
                foreach ($subParams as $param) {
                    $params[] = $param;
                }
                continue;
            }
            // Raw SQL
            if (!empty($where['raw'])) {
                $sql .= $where['sql'];
                foreach ($where['params'] ?? [] as $param) {
                    $params[] = $param;
                }
                continue;
            }
            // IN / NOT IN
            if (!empty($where['is_array'])) {
                $placeholders = implode(',', array_fill(0, count($where['value']), '?'));
                $sql .= "{$where['column']} {$where['operator']} ($placeholders)";
                foreach ($where['value'] as $value) {
                    $params[] = $value;
                }
                continue;
            }
            // NULL
            if (array_key_exists('value', $where) && $where['value'] === null) {
                if ($where['operator'] ?? '=') {
                    $sql .= "{$where['column']} IS NULL";
                } elseif (in_array($where['operator'], ['!=', '<>'])) {
                    $sql .= "{$where['column']} IS NOT NULL";
                }
                continue;
            }
            if (!empty($where['between'])) {
                $sql .= "{$where['column']} BETWEEN ? AND ?";
                $params[] = $where['value'][0];
                $params[] = $where['value'][1];
                continue;
            }
            $sql .= "{$where['column']} {$where['operator']} ?";
            $params[] = $where['value'];
        }
        
        return $sql;
    }

    /**
     * DELETE
     */
    public function delete(): bool
    {
        $db = $this->model::getDb();
        $table = $this->model::getTable();
        $params = [];
        $sql = "DELETE FROM {$table}";
        $sql .= $this->buildWhereClause($params);
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * UPDATE
     */
    public function update(array $values): bool
    {
        $db = $this->model::getDb();
        $table = $this->model::getTable();

        $params = [];

        $sets = [];

        foreach ($values as $column => $value) {
            $sets[] = "{$column} = ?";
            $params[] = $value;
        }
        $sql = "UPDATE {$table} SET " . implode(', ', $sets);
        $sql .= $this->buildWhereClause($params);
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateRaw(array $updates, array $bindings = []): bool
    {
        $db = $this->model::getDb();
        $table = $this->model::getTable();
        if (empty($updates)) {
            return false;
        }
        $sets = [];
        foreach ($updates as $column => $expression) {
            $sets[] = "{$column} = {$expression}";
        }
        $params = [];
        // Bindings belonging to the SET expressions first.
        foreach ($bindings as $binding) {
            $params[] = $binding;
        }
        $sql = "UPDATE {$table} SET " . implode(', ', $sets);
        $sql .= $this->buildWhereClause($params);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    //Where Month
    public function whereMonth(string $column, string $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type'      => 'AND',
            'column'    => "MONTH($column)",
            'operator'  => strtoupper($operator),
            'value'     => $value,
            'is_array'  => false,
        ];

        return $this;
    }

    public function orWhereMonth(string $column, string $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type'      => 'OR',
            'column'    => "MONTH($column)",
            'operator'  => strtoupper($operator),
            'value'     => $value,
            'is_array'  => false,
        ];

        return $this;
    }
    // 

    public function whereYear(string $column, string $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type'      => 'AND',
            'column'    => "YEAR($column)",
            'operator'  => strtoupper($operator),
            'value'     => $value,
            'is_array'  => false,
        ];

        return $this;
    }

    public function orWhereYear(string $column, string $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type'      => 'OR',
            'column'    => "YEAR($column)",
            'operator'  => strtoupper($operator),
            'value'     => $value,
            'is_array'  => false,
        ];

        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'AND',
            'raw' => true,
            'sql' => "{$column} IS NULL",
            'params' => [],
        ];

        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'AND',
            'raw' => true,
            'sql' => "{$column} IS NOT NULL",
            'params' => [],
        ];

        return $this;
    }
    
    public function paginate(int $perPage = 15, int $page = 1): array
    {
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        // Total records
        $countQuery = clone $this;
        $countQuery->select = 'COUNT(*) as total';
        $countQuery->orderBy = null;
        $countQuery->limitValue = null;
        $countQuery->offset = null;

        $total = $countQuery->count();

        // Fetch current page
        $this->limit($perPage);
        // $this->offset($offset);
        $this->offset(($page - 1) * $perPage);

        return [
            'data' => $this->get(),
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => max(1, (int) ceil($total / $perPage)),
            ],
        ];
    }
}