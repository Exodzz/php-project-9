<?php

namespace Hexlet\Code;

use Exception;
use PDO;

/**
 * Создание класса Connection
 */
class Connection
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = $this->connect();
    }

    /**
     * Подключение к базе данных и возврат экземпляра объекта \PDO
     * @return PDO
     * @throws Exception
     */
    public function connect()
    {
        if ($_ENV) {
            $database = $_ENV;
        }

        if (isset($_ENV['host'])) {
            $params['host'] = $database['host'];
            $params['port'] = isset($database['port']) ? $database['port'] : null;
            $params['database'] = isset($database['database']) ? ltrim($database['database'], '/') : null;
            $params['user'] = isset($database['user']) ? $database['user'] : null;
            $params['password'] = isset($database['password']) ? $database['password'] : null;
        } else {
        // чтение параметров в файле конфигурации
            $params = parse_ini_file(__DIR__ . '/../database.env');
        }

        // подключение к базе данных postgresql
        $conStr = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            $params['host'],
            $params['port'],
            $params['database'],
            $params['user'],
            $params['password']
        );

        $pdo = new PDO($conStr);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }

    /**
     * Создание новой записи URL
     * @param string $name
     * @return int
     */
    public function createUrl(string $name): int
    {
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Получение URL по ID
     * @param int $id
     * @return array|false
     */
    public function getUrlById(int $id)
    {
        $sql = "SELECT * FROM urls WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Получение URL по имени
     * @param string $name
     * @return array|false
     */
    public function getUrlByName(string $name)
    {
        $sql = "SELECT * FROM urls WHERE name = :name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $name]);
        return $stmt->fetch();
    }

    /**
     * Получение всех URL
     * @return array
     */
    public function getAllUrls(): array
    {
        $sql = "SELECT * FROM urls ORDER BY created_at DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Создание новой проверки URL
     * @param int $urlId
     * @param array $checkData
     * @return int
     */
    public function createUrlCheck(int $urlId, array $checkData): int
    {
        $sql = "INSERT INTO url_checks (url_id, status_code, h1, title, description, created_at) 
                VALUES (:url_id, :status_code, :h1, :title, :description, :created_at) 
                RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'url_id' => $urlId,
            'status_code' => $checkData['status_code'],
            'h1' => $checkData['h1'] ?? null,
            'title' => $checkData['title'] ?? null,
            'description' => $checkData['description'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Получение всех проверок для URL
     * @param int $urlId
     * @return array
     */
    public function getUrlChecks(int $urlId): array
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['url_id' => $urlId]);
        return $stmt->fetchAll();
    }

    /**
     * Получение последней проверки для URL
     * @param int $urlId
     * @return array|false
     */
    public function getLastUrlCheck(int $urlId)
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['url_id' => $urlId]);
        return $stmt->fetch();
    }
}
