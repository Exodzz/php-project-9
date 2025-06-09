<?php

namespace Hexlet\Code;

use Exception;
use PDO;
use PDOStatement;

/**
 * Создание класса Connection
 */
class Connection
{
    private PDO $pdo;
    private array $validateConfig = [];
    public function __construct(array $validateConfig)
    {
        $this->validateConfig = $validateConfig;
        $this->pdo = $this->connect();
        $this->importDB();
    }

    /**
     * Подключение к базе данных и возврат экземпляра объекта \PDO
     * @return PDO
     * @throws Exception
     */
    public function connect(): PDO
    {
        if (isset($_ENV['DATABASE_URL'])) {
            $databaseUrl = parse_url($_ENV['DATABASE_URL']);
            if ($databaseUrl === false) {
                throw new Exception('Invalid DATABASE_URL format');
            }
            if (!isset($databaseUrl['host'], $databaseUrl['path'], $databaseUrl['user'], $databaseUrl['pass'])) {
                throw new Exception('Missing required database parameters in DATABASE_URL');
            }
            $params = [
                'host'     => $databaseUrl['host'],
                'port'     => $databaseUrl['port'] ?? 5432,
                'database' => ltrim($databaseUrl['path'], '/'),
                'user'     => $databaseUrl['user'],
                'pass'     => $databaseUrl['pass']
            ];
        } elseif (isset($_ENV['host'])) {
            $params = [
                'host'     => $_ENV['host'],
                'port'     => $_ENV['port'] ?? 5432,
                'database' => isset($_ENV['database']) ? ltrim($_ENV['database'], '/') : '',
                'user'     => $_ENV['user'] ?? '',
                'pass'     => $_ENV['password'] ?? $_ENV['pass'] ?? ''
            ];
        } else {
            $params = parse_ini_file(__DIR__ . '/../database.env');
            if ($params === false) {
                throw new Exception('Failed to parse database.env file');
            }
        }

        $requiredParams = ['host', 'database', 'user', 'pass'];
        foreach ($requiredParams as $param) {
            if (empty($params[$param])) {
                throw new Exception("Missing required database parameter: {$param}");
            }
        }

        $conStr = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            $params['host'],
            $params['port'],
            $params['database'],
            $params['user'],
            $params['pass']
        );

        $pdo = new PDO($conStr);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }

    /**
     * Проверка наличия таблиц и их импорт при необходимости
     * @return void
     * @throws Exception
     */
    public function importDB(): void
    {
        $tables = ['urls', 'urls_checks'];
        $existingTables = [];

        $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
        $result = $this->pdo->query($sql);
        if ($result === false) {
            throw new Exception('Failed to query database tables');
        }
        while ($row = $result->fetch()) {
            $existingTables[] = $row['table_name'];
        }

        if (count(array_diff($tables, $existingTables)) > 0) {
            $sql = file_get_contents(__DIR__ . '/../database.sql');
            if ($sql === false) {
                throw new Exception('Failed to read database.sql file');
            }
            $result = $this->pdo->exec($sql);
            if ($result === false) {
                throw new Exception('Failed to execute database.sql');
            }
        }
    }

    /**
     * Создание новой записи URL
     * @param string $name
     * @return int
     * @throws Exception
     */
    public function createUrl(string $name): int
    {
        $double = $this->getUrlByName($name);
        if ($double) {
            throw new \RuntimeException($double['id']);
        }
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw new Exception('Failed to prepare SQL statement');
        }
        $stmt->execute([
            'name'       => $name,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $result = $stmt->fetchColumn();
        if ($result === false) {
            throw new Exception('Failed to get inserted ID');
        }
        return (int)$result;
    }

    /**
     * Валидация полей
     */
    private function validateFields(array &$fields): array
    {
        foreach ($this->validateConfig as $field => $length) {
            if ($fields[$field] > $length) {
                $fields[$field] = mb_strimwidth($fields[$field], 0, $length - 3, "...", 'UTF-8');
            }
        }
        return $fields;
    }

    /**
     * Получение URL по ID
     * @param int $id
     * @return array|false
     */
    public function getUrlById(int $id): array|false
    {
        $sql = "SELECT * FROM urls WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            return false;
        }
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Получение URL по имени
     * @param string $name
     * @return array|false
     */
    public function getUrlByName(string $name): array|false
    {
        $parsedUrl = parse_url($name);
        $name = is_array($parsedUrl) && isset($parsedUrl['host']) ? $parsedUrl['host'] : $name;

        $sql = "SELECT * FROM urls WHERE name LIKE :name";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            return false;
        }
        $stmt->execute(['name' => "%{$name}%"]);
        return $stmt->fetch();
    }

    /**
     * Получение всех URL
     * @return array
     * @throws Exception
     */
    public function getAllUrls(): array
    {
        $sql = "SELECT * FROM urls ORDER BY created_at DESC";
        $result = $this->pdo->query($sql);
        if ($result === false) {
            throw new Exception('Failed to query URLs');
        }
        return $result->fetchAll();
    }

    /**
     * Создание новой проверки URL
     * @param int $urlId
     * @param array $checkData
     * @return void
     * @throws Exception
     */
    public function createUrlCheck(int $urlId, array $checkData): void
    {
        $this->validateFields($checkData);
        $sql = 'INSERT INTO urls_checks (url_id, status_code, h1, title, description, created_at) 
                VALUES (:url_id, :status_code, :h1, :title, :description, :created_at)';

        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw new Exception('Failed to prepare SQL statement');
        }
        $stmt->execute([
            'url_id'      => $urlId,
            'status_code' => $checkData['status_code'],
            'h1'          => $checkData['h1'],
            'title'       => $checkData['title'],
            'description' => $checkData['description'],
            'created_at'  => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Получение всех проверок для URL
     * @param int $urlId
     * @return array
     * @throws Exception
     */
    public function getUrlChecks(int $urlId): array
    {
        $sql = "SELECT * FROM urls_checks WHERE url_id = :url_id ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw new Exception('Failed to prepare SQL statement');
        }
        $stmt->execute(['url_id' => $urlId]);
        return $stmt->fetchAll();
    }

    /**
     * Получение последней проверки для URL
     * @param int $urlId
     * @return array|false
     */
    public function getLastUrlCheck(int $urlId): array|false
    {
        $sql = "SELECT * FROM urls_checks WHERE url_id = :url_id ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            return false;
        }
        $stmt->execute(['url_id' => $urlId]);
        return $stmt->fetch();
    }
}
