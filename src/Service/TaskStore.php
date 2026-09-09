<?php

declare(strict_types=1);

namespace App\Service;

use PDO;

/** Хранилище практических задач и вариантов решений. */
final class TaskStore
{
    private ?PDO $connection = null;

    public function __construct(private readonly string $databaseFile)
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
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $this->connection = new PDO(
            'sqlite:' . $this->databaseFile,
            options: [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ],
        );
        return $this->connection;
    }
}
