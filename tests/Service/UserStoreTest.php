<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\UserStore;
use PHPUnit\Framework\TestCase;

/** Проверяет хранение пользовательского прогресса. */
final class UserStoreTest extends TestCase
{
    private string $databaseFile;

    protected function setUp(): void
    {
        $this->databaseFile = sys_get_temp_dir().'/php-recall-users-'.bin2hex(random_bytes(8)).'.sqlite';
    }

    protected function tearDown(): void
    {
        if (is_file($this->databaseFile)) {
            unlink($this->databaseFile);
        }
    }

    public function testStoresFrameworkProgressSeparately(): void
    {
        $store = new UserStore($this->databaseFile);
        $userId = $store->create('student@example.com', 'secret-password');

        $store->setFrameworkKnown($userId, 'service-container', true);
        self::assertSame(['service-container'], $store->knownFrameworks($userId));
        self::assertSame([], $store->knownConcepts($userId));
        self::assertSame([], $store->knownFunctions($userId));

        $store->setFrameworkKnown($userId, 'service-container', false);
        self::assertSame([], $store->knownFrameworks($userId));
    }
}
