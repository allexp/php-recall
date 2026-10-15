<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Создаёт таблицу пользователей административной части сайта. */
final class Version20261015000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Создаёт таблицу администраторов в базе материалов';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof SQLitePlatform,
            'Миграция поддерживает только SQLite.',
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS admin_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                roles TEXT NOT NULL DEFAULT '[\"ROLE_ADMIN\"]',
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )",
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS admin_users');
    }
}
