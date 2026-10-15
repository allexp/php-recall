<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\AdminUser;
use PHPUnit\Framework\TestCase;

/** Проверяет данные пользователя административной части. */
final class AdminUserEntityTest extends TestCase
{
    public function testNormalizesEmailAndAlwaysHasAdminRole(): void
    {
        $admin = (new AdminUser())
            ->setEmail('  ADMIN@Example.COM  ')
            ->setRoles(['ROLE_EDITOR', 'ROLE_EDITOR']);

        self::assertSame('admin@example.com', $admin->getEmail());
        self::assertSame('admin@example.com', $admin->getUserIdentifier());
        self::assertSame(['ROLE_EDITOR', 'ROLE_ADMIN'], $admin->getRoles());
    }

    public function testStoresOnlyPasswordHash(): void
    {
        $admin = (new AdminUser())->setPassword('$2y$hash');

        self::assertSame('$2y$hash', $admin->getPassword());
    }

    public function testInitializesTimestamps(): void
    {
        $admin = new AdminUser();

        self::assertNotFalse(\DateTimeImmutable::createFromFormat(DATE_ATOM, $admin->getCreatedAt()));
        self::assertSame($admin->getCreatedAt(), $admin->getUpdatedAt());
    }
}
