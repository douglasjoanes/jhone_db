<?php

namespace Jhonestack\Database;

use PDO;
use PDOException;
use InvalidArgumentException;

class DatabaseFactory
{
    private static ?PDO $instance = null;

    /**
     * Retorna conexão única injetando as configurações
     */
    public static function create(array $config): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::validateConfig($config);

        try {
            $dsn = self::buildDsn($config);

            self::$instance = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                self::getOptions($config)
            );

        } catch (PDOException $e) {
            throw new PDOException(
                "Falha ao conectar com o banco de dados.",
                (int) $e->getCode(),
                $e
            );
        }

        return self::$instance;
    }

    private static function validateConfig(array $config): void
    {
        $required = ['driver', 'host', 'port', 'database', 'username', 'password'];

        foreach ($required as $key) {
            if (!isset($config[$key]) || empty($config[$key])) {
                throw new InvalidArgumentException(
                    "Configuração de banco inválida: {$key} ausente ou vazio"
                );
            }
        }
    }

    private static function buildDsn(array $config): string
    {
        return match ($config['driver']) {
            'mysql' => sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset'] ?? 'utf8mb4'
            ),
            'pgsql' => sprintf(
                "pgsql:host=%s;port=%s;dbname=%s;options='--client_encoding=%s'",
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset'] ?? 'UTF8'
            ),
            default => throw new InvalidArgumentException(
                "Driver não suportado: {$config['driver']}"
            ),
        };
    }

    private static function getOptions(array $config): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_CASE => PDO::CASE_NATURAL,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => $config['timeout'] ?? 5,
        ];
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}