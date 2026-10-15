<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

/** Пересоздаёт схему только в изолированной базе test-окружения. */
trait ResetsDatabase
{
    protected function resetDatabase(): EntityManagerInterface
    {
        if (static::$kernel === null || static::$kernel->getEnvironment() !== 'test') {
            throw new \LogicException('Сброс базы разрешён только в test-окружении.');
        }

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $databasePath = (string) ($entityManager->getConnection()->getParams()['path'] ?? '');
        $cacheDirectory = rtrim(str_replace('\\', '/', static::$kernel->getCacheDir()), '/').'/';
        if (!str_starts_with(str_replace('\\', '/', $databasePath), $cacheDirectory)) {
            throw new \LogicException('Тестовая база должна находиться в каталоге кэша test-окружения.');
        }

        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);

        return $entityManager;
    }
}
