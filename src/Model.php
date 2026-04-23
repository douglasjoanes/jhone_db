<?php

namespace Jhonestack\Database;

use PDO;
use Exception;

abstract class Model
{
    protected string $table;
    protected array $attributes = [];
    protected array $relations = [];
    protected array $fillable = [];
    protected bool $usesSoftDeletes = false;
    protected static PDO $db;

    public function __construct(array $data = [])
    {
        $this->fill($data);
    }

    public static function setConnection(PDO $pdo): void
    {
        self::$db = $pdo;
    }

    public static function db(): PDO
    {
        if (!isset(self::$db)) {
            throw new Exception("Conexão PDO não definida. Use Model::setConnection(\$pdo) antes de realizar consultas.");
        }
        return self::$db;
    }

    public function fill(array $data): void
    {
        foreach ($data as $key => $value) {
            if (empty($this->fillable) || in_array($key, $this->fillable)) {
                $this->attributes[$key] = $value;
            }
        }
    }

    public function __get($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        if (method_exists($this, $key)) {
            return $this->relations[$key] = $this->$key();
        }

        return null;
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public static function query(): QueryBuilder
    {
        $instance = new static();
        $query = new QueryBuilder(self::db(), $instance->table, static::class);

        if ($instance->usesSoftDeletes) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }

    public static function find($id)
    {
        return static::query()->where('id', $id)->first();
    }

    public static function all()
    {
        return static::query()->get();
    }

    public function save()
    {
        if (isset($this->attributes['id'])) {
            $id = $this->attributes['id'];
            $data = $this->attributes;
            unset($data['id']);

            return static::query()->where('id', $id)->update($data);
        }

        $id = static::query()->create($this->attributes);
        $this->attributes['id'] = $id;
        return $id;
    }

    public function delete()
    {
        if (!isset($this->attributes['id'])) {
            throw new Exception("Model sem ID não pode ser deletado.");
        }

        if ($this->usesSoftDeletes) {
            $this->attributes['deleted_at'] = date('Y-m-d H:i:s');
            return $this->save();
        }

        return static::query()->where('id', $this->attributes['id'])->delete();
    }

    // Relacionamentos simplificados
    protected function hasMany($related, $foreignKey, $localKey = 'id') {
        return $related::query()->where($foreignKey, $this->$localKey)->get();
    }

    protected function hasOne($related, $foreignKey, $localKey = 'id') {
        return $related::query()->where($foreignKey, $this->$localKey)->first();
    }

    protected function belongsTo($related, $foreignKey, $ownerKey = 'id') {
        return $related::query()->where($ownerKey, $this->$foreignKey)->first();
    }
}