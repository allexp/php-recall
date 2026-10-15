<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\UserStore;
use App\Tests\Support\CreatesTestDatabase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/** Проверяет хранение пользовательского прогресса. */
final class UserStoreTest extends TestCase
{
    use CreatesTestDatabase;

    private Connection $database;

    protected function setUp(): void
    {
        $this->database = $this->createTestDatabase();
        $this->createUserSchema($this->database);
    }

    public function testStoresFrameworkProgressSeparately(): void
    {
        $store = new UserStore($this->database);
        $userId = $store->create('student@example.com', 'secret-password');

        $store->setFrameworkKnown($userId, 'service-container', true);
        self::assertSame(['service-container'], $store->knownFrameworks($userId));
        self::assertSame([], $store->knownConcepts($userId));
        self::assertSame([], $store->knownFunctions($userId));

        $store->setFrameworkKnown($userId, 'service-container', false);
        self::assertSame([], $store->knownFrameworks($userId));
    }
}
