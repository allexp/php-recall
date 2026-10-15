<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;
use PDO;

/** Хранилище практических задач и вариантов решений. */
final class TaskStore
{
    private ?PDO $pdo = null;

    public function __construct(private readonly Connection $database)
    {
    }

    /**
     * Возвращает случайную задачу без решения.
     *
     * @return array{id: int, difficulty: string, title: string, description: string, starter_code: string}|null
     */
    public function random(string $difficulty): ?array
    {
        $statement = $this->connection()->prepare(
            <<<'SQL'
                SELECT id, difficulty, title, description, starter_code
                FROM tasks
                WHERE difficulty = :difficulty
                ORDER BY RANDOM()
                LIMIT 1
                SQL,
        );
        $statement->execute(['difficulty' => $difficulty]);

        $task = $statement->fetch();

        return is_array($task) ? $task : null;
    }

    /** Возвращает вариант решения по идентификатору задачи. */
    public function solution(int $id): ?string
    {
        $statement = $this->connection()->prepare(
            'SELECT solution_code FROM tasks WHERE id = :id',
        );
        $statement->execute(['id' => $id]);

        $solution = $statement->fetchColumn();

        return is_string($solution) ? $solution : null;
    }

    private function connection(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $connection = $this->database->getNativeConnection();
        if (!$connection instanceof PDO) {
            throw new \LogicException('Для работы хранилища требуется PDO-подключение.');
        }

        $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $connection->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
        $this->pdo = $connection;

        return $this->pdo;
    }
}
