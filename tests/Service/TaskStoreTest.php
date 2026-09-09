<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\TaskStore;
use PDO;
use PHPUnit\Framework\TestCase;

/** Проверяет хранилище практических задач. */
final class TaskStoreTest extends TestCase
{
    public function testReturnsTaskAndKeepsSolutionSeparate(): void
    {
        $databaseFile = sys_get_temp_dir() . '/php-recall-tasks-' . bin2hex(random_bytes(8)) . '.sqlite';
        try {
            $connection = new PDO('sqlite:'.$databaseFile);
            $connection->exec(
                "CREATE TABLE tasks (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    difficulty TEXT NOT NULL,
                    title TEXT NOT NULL,
                    description TEXT NOT NULL,
                    starter_code TEXT NOT NULL,
                    solution_code TEXT NOT NULL
                );
                INSERT INTO tasks (difficulty, title, description, starter_code, solution_code)
                VALUES ('easy', 'Тестовая задача', 'Описание', '<?php', '<?php echo 1;')",
            );
            unset($connection);

            $store = new TaskStore($databaseFile);
            $task = $store->random('easy');

            self::assertNotNull($task);
            self::assertArrayNotHasKey('solution_code', $task);
            self::assertStringContainsString('<?php', $store->solution((int) $task['id']));
            self::assertNull($store->random('unknown'));
        } finally {
            unset($store);
            gc_collect_cycles();
            if (is_file($databaseFile)) {
                unlink($databaseFile);
            }
        }
    }
}
