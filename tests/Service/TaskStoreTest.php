<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\TaskStore;
use PHPUnit\Framework\TestCase;

/** Проверяет хранилище практических задач. */
final class TaskStoreTest extends TestCase
{
    public function testReturnsTaskAndKeepsSolutionSeparate(): void
    {
        $databaseFile = sys_get_temp_dir().'/php-recall-tasks-'.bin2hex(random_bytes(8)).'.sqlite';
        try {
            $store = new TaskStore($databaseFile);
            $task = $store->random('easy');

            self::assertNotNull($task);
            self::assertArrayNotHasKey('solution_code', $task);
            self::assertStringContainsString('<?php', $store->solution((int) $task['id']));
            self::assertNull($store->random('unknown'));
        } finally {
            unset($store);
            gc_collect_cycles();
            if (is_file($databaseFile)) unlink($databaseFile);
        }
    }
}
