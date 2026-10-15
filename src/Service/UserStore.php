<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;
use PDO;

/** Хранит учётные записи пользователей и их учебный прогресс. */
final class UserStore
{
    private ?PDO $pdo = null;

    public function __construct(private readonly Connection $database)
    {
    }

    /** Создаёт пользователя и возвращает его идентификатор. */
    public function create(string $email, string $password): int
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO users (email, password_hash, created_at)
             VALUES (:email, :password_hash, :created_at)
             RETURNING id'
        );
        $statement->execute([
            'email' => $this->normaliseEmail($email),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);

        return (int) $statement->fetchColumn();
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
                'INSERT INTO known_functions (user_id, function_name, created_at)
                 VALUES (:user_id, :function_name, :created_at)
                 ON CONFLICT (user_id, function_name) DO NOTHING'
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

    /** Возвращает список концепций, которые пользователь отметил как известные. */
    public function knownConcepts(int $userId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT concept_slug FROM known_concepts WHERE user_id = :user_id ORDER BY concept_slug'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Добавляет или удаляет концепцию из списка известных пользователю. */
    public function setConceptKnown(int $userId, string $slug, bool $known): void
    {
        if ($known) {
            $statement = $this->connection()->prepare(
                'INSERT INTO known_concepts (user_id, concept_slug, created_at)
                 VALUES (:user_id, :concept_slug, :created_at)
                 ON CONFLICT (user_id, concept_slug) DO NOTHING'
            );
            $statement->execute([
                'user_id' => $userId,
                'concept_slug' => $slug,
                'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ]);

            return;
        }

        $statement = $this->connection()->prepare(
            'DELETE FROM known_concepts WHERE user_id = :user_id AND concept_slug = :concept_slug'
        );
        $statement->execute(['user_id' => $userId, 'concept_slug' => $slug]);
    }

    /** Возвращает список карточек фреймворков, которые пользователь отметил как известные. */
    public function knownFrameworks(int $userId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT framework_slug FROM known_frameworks WHERE user_id = :user_id ORDER BY framework_slug'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Добавляет или удаляет карточку фреймворка из списка известных пользователю. */
    public function setFrameworkKnown(int $userId, string $slug, bool $known): void
    {
        if ($known) {
            $statement = $this->connection()->prepare(
                'INSERT INTO known_frameworks (user_id, framework_slug, created_at)
                 VALUES (:user_id, :framework_slug, :created_at)
                 ON CONFLICT (user_id, framework_slug) DO NOTHING'
            );
            $statement->execute([
                'user_id' => $userId,
                'framework_slug' => $slug,
                'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ]);

            return;
        }

        $statement = $this->connection()->prepare(
            'DELETE FROM known_frameworks WHERE user_id = :user_id AND framework_slug = :framework_slug'
        );
        $statement->execute(['user_id' => $userId, 'framework_slug' => $slug]);
    }

    private function normaliseEmail(string $email): string
    {
        return mb_strtolower(trim($email));
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
