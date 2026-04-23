<?php

namespace Jhonestack\Database;

use PDO;
use Exception;

class QueryBuilder
{
    protected string $table;
    protected PDO $db;
    protected string $modelClass;

    protected array $wheres = [];
    protected array $bindings = [];
    protected array $orderBy = [];
    protected array $joins = [];

    protected ?int $limit = null;
    protected ?int $offset = null;
    protected string $select = '*';

    protected array $allowedOperators = [
        '=', '>', '<', '>=', '<=', '!=', '<>', 'LIKE'
    ];

    public function __construct(PDO $db, string $table, string $modelClass)
    {
        $this->db = $db;
        $this->table = $table;
        $this->modelClass = $modelClass;
    }

    /* =========================
     * SELECT & FILTERS
     * ========================= */

    public function select(array|string $fields): self
    {
        $this->select = is_array($fields) ? implode(', ', $fields) : $fields;
        return $this;
    }

    public function where(string $field, $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->validateField($field);
        $operator = $this->validateOperator($operator);
        $param = $this->param($field);

        $this->wheres[] = "AND $field $operator :$param";
        $this->bindings[$param] = $value;

        return $this;
    }

    public function orWhere(string $field, $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->validateField($field);
        $operator = $this->validateOperator($operator);
        $param = $this->param($field);

        $this->wheres[] = "OR $field $operator :$param";
        $this->bindings[$param] = $value;

        return $this;
    }

    public function whereIn(string $field, array $values): self
    {
        $this->validateField($field);
        $placeholders = [];

        foreach ($values as $i => $value) {
            $param = $this->param($field . $i);
            $placeholders[] = ":$param";
            $this->bindings[$param] = $value;
        }

        $this->wheres[] = "AND $field IN (" . implode(',', $placeholders) . ")";
        return $this;
    }

    public function whereNull(string $field): self
    {
        $this->validateField($field);
        $this->wheres[] = "AND $field IS NULL";
        return $this;
    }

    /* =========================
     * JOINS & ORDER
     * ========================= */

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = "$type JOIN $table ON $first $operator $second";
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) throw new Exception("Direção inválida");
        
        $this->validateField($field);
        $this->orderBy[] = "$field $direction";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /* =========================
     * EXECUTION (READ)
     * ========================= */

    public function get(): array
    {
        $stmt = $this->db->prepare($this->buildSelect());
        $stmt->execute($this->bindings);

        $results = array_map(fn($item) => $this->hydrate($item), $stmt->fetchAll());
        $this->reset();
        return $results;
    }

    public function first()
    {
        $this->limit(1);
        $stmt = $this->db->prepare($this->buildSelect());
        $stmt->execute($this->bindings);
        $result = $stmt->fetch();
        $this->reset();
        return $result ? $this->hydrate($result) : null;
    }

    public function firstOrFail()
    {
        $result = $this->first();
        if (!$result) throw new Exception("Registro não encontrado na tabela {$this->table}.");
        return $result;
    }

    public function count(): int
    {
        $clone = clone $this;
        $clone->select = 'COUNT(*) as total';
        $clone->orderBy = [];
        $clone->limit = null;
        $clone->offset = null;

        $stmt = $this->db->prepare($clone->buildSelect());
        $stmt->execute($clone->bindings);
        $res = $stmt->fetch();
        return (int) ($res->total ?? 0);
    }

    /* =========================
     * PERSISTENCE (CUD)
     * ========================= */

    public function create(array $data)
    {
        $fields = array_keys($data);
        $params = array_map(fn($f) => ":$f", $fields);

        $sql = "INSERT INTO {$this->table} (" . implode(',', $fields) . ") VALUES (" . implode(',', $params) . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return $this->db->lastInsertId();
    }

    public function update(array $data): bool
    {
        $fields = [];
        $bindings = [];

        foreach ($data as $key => $value) {
            $param = "upd_$key";
            $fields[] = "$key = :$param";
            $bindings[$param] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . $this->buildWhere();
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(array_merge($bindings, $this->bindings));
    }

    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->table}" . $this->buildWhere();
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($this->bindings);
    }

    /* =========================
     * BUILDERS & INTERNALS
     * ========================= */

    protected function buildSelect(): string
    {
        $sql = "SELECT {$this->select} FROM {$this->table}";
        if ($this->joins) $sql .= " " . implode(' ', $this->joins);
        $sql .= $this->buildWhere();
        if ($this->orderBy) $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        if ($this->limit !== null) $sql .= " LIMIT {$this->limit}";
        if ($this->offset !== null) $sql .= " OFFSET {$this->offset}";
        return $sql;
    }

    protected function buildWhere(): string
    {
        if (!$this->wheres) return '';
        $conditions = preg_replace('/^(AND|OR)/', '', implode(' ', $this->wheres));
        return " WHERE $conditions";
    }

    protected function hydrate($data)
    {
        if (!$data) return null;
        $model = new $this->modelClass();
        $model->fill((array) $data);
        return $model;
    }

    protected function validateOperator(string $operator): string
    {
        $operator = strtoupper($operator);
        if (!in_array($operator, $this->allowedOperators)) throw new Exception("Operador inválido: $operator");
        return $operator;
    }

    protected function validateField(string $field): void
    {
        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $field)) throw new Exception("Nome de campo inválido: $field");
    }

    protected function param(string $field): string
    {
        return str_replace('.', '_', $field) . '_' . count($this->bindings);
    }

    protected function reset(): void
    {
        $this->wheres = [];
        $this->bindings = [];
        $this->orderBy = [];
        $this->joins = [];
        $this->limit = null;
        $this->offset = null;
        $this->select = '*';
    }
}