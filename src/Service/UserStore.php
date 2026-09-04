<?php

declare(strict_types=1);

namespace App\Service;

use PDO;

/** Хранит учётные записи пользователей в локальной базе SQLite. */
final class UserStore
{
    private ?PDO $connection = null;

    public function __construct(private readonly string $databaseFile)
    {
    }

    /** Создаёт пользователя и возвращает его идентификатор. */
    public function create(string $email, string $password): int
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO users (email, password_hash, created_at) VALUES (:email, :password_hash, :created_at)'
        );
        $statement->execute([
            'email' => $this->normaliseEmail($email),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);

        return (int) $this->connection()->lastInsertId();
    }

    /** Возвращает пользователя по email или null. */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->connection()->prepare('SELECT id, email, password_hash FROM users WHERE email = :email');
        $statement->execute(['email' => $this->normaliseEmail($email)]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    /** Возвращает список функций, которые пользователь отметил как известные. */
    public function knownFunctions(int $userId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT function_name FROM known_functions WHERE user_id = :user_id ORDER BY function_name'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Добавляет или удаляет функцию из списка известных пользователю. */
    public function setFunctionKnown(int $userId, string $function, bool $known): void
    {
        if ($known) {
            $statement = $this->connection()->prepare(
                'INSERT OR IGNORE INTO known_functions (user_id, function_name, created_at)
                 VALUES (:user_id, :function_name, :created_at)'
            );
            $statement->execute([
                'user_id' => $userId,
                'function_name' => $function,
                'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ]);

            return;
        }

        $statement = $this->connection()->prepare(
            'DELETE FROM known_functions WHERE user_id = :user_id AND function_name = :function_name'
        );
        $statement->execute(['user_id' => $userId, 'function_name' => $function]);
    }

    private function normaliseEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function connection(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $directory = dirname($this->databaseFile);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException('Не удалось создать каталог базы пользователей.');
        }

        $this->connection = new PDO('sqlite:'.$this->databaseFile, options: [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->connection->exec('PRAGMA foreign_keys = ON');
        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL COLLATE NOCASE UNIQUE,
                password_hash TEXT NOT NULL,
                created_at TEXT NOT NULL
            )'
        );
        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS known_functions (
                user_id INTEGER NOT NULL,
                function_name TEXT NOT NULL,
                created_at TEXT NOT NULL,
                PRIMARY KEY (user_id, function_name),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )'
        );

        return $this->connection;
    }
}
