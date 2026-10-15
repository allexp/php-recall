<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\TaskStore;
use App\Tests\Support\CreatesTestDatabase;
use PHPUnit\Framework\TestCase;

/** Проверяет хранилище практических задач. */
final class TaskStoreTest extends TestCase
{
    use CreatesTestDatabase;

    public function testReturnsTaskAndKeepsSolutionSeparate(): void
    {
        $database = $this->createTestDatabase();
        $database->executeStatement('CREATE TABLE tasks (id INTEGER PRIMARY KEY AUTOINCREMENT, difficulty TEXT NOT NULL, title TEXT NOT NULL, description TEXT NOT NULL, starter_code TEXT NOT NULL, solution_code TEXT NOT NULL)');
        $database->executeStatement("INSERT INTO tasks (difficulty, title, description, starter_code, solution_code) VALUES ('easy', 'Тестовая задача', 'Описание', '<?php', '<?php echo 1;')");

        $store = new TaskStore($database);
        $task = $store->random('easy');

        self::assertNotNull($task);
        self::assertArrayNotHasKey('solution_code', $task);
        self::assertStringContainsString('<?php', $store->solution((int) $task['id']));
        self::assertNull($store->random('unknown'));
    }
}
