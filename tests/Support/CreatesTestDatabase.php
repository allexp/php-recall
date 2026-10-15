<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PDO;

/** Создаёт изолированную DBAL-базу для модульных тестов хранилищ. */
trait CreatesTestDatabase
{
    protected function createTestDatabase(): Connection
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $pdo = $connection->getNativeConnection();
        if (!$pdo instanceof PDO) {
            throw new \LogicException('Тестам требуется PDO-подключение.');
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $connection;
    }

    protected function createMaterialSchema(Connection $connection): void
    {
        $connection->executeStatement('CREATE TABLE material_types (id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT NOT NULL UNIQUE, title TEXT NOT NULL)');
        $connection->executeStatement('CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, type_id INTEGER NOT NULL, code TEXT NOT NULL, title TEXT NOT NULL, position INTEGER NOT NULL DEFAULT 0, UNIQUE(type_id, code), FOREIGN KEY(type_id) REFERENCES material_types(id) ON DELETE CASCADE)');
        $connection->executeStatement("CREATE TABLE materials (id INTEGER PRIMARY KEY AUTOINCREMENT, type_id INTEGER NOT NULL, category_id INTEGER, title TEXT NOT NULL, slug TEXT NOT NULL, definition TEXT NOT NULL DEFAULT '', short_description TEXT NOT NULL DEFAULT '', full_description TEXT NOT NULL DEFAULT '', content_format TEXT NOT NULL DEFAULT 'html', source_url TEXT, position INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL, updated_at TEXT NOT NULL, UNIQUE(type_id, slug), FOREIGN KEY(type_id) REFERENCES material_types(id) ON DELETE CASCADE, FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL)");
        $connection->executeStatement("CREATE TABLE material_sections (id INTEGER PRIMARY KEY AUTOINCREMENT, material_id INTEGER NOT NULL, title TEXT NOT NULL, slug TEXT NOT NULL, description TEXT NOT NULL DEFAULT '', position INTEGER NOT NULL DEFAULT 0, UNIQUE(material_id, slug), FOREIGN KEY(material_id) REFERENCES materials(id) ON DELETE CASCADE)");
        $connection->executeStatement("CREATE TABLE code_examples (id INTEGER PRIMARY KEY AUTOINCREMENT, material_id INTEGER NOT NULL, section_id INTEGER, title TEXT NOT NULL DEFAULT '', language TEXT NOT NULL, code TEXT NOT NULL, position INTEGER NOT NULL DEFAULT 0, FOREIGN KEY(material_id) REFERENCES materials(id) ON DELETE CASCADE, FOREIGN KEY(section_id) REFERENCES material_sections(id) ON DELETE CASCADE)");
    }

    protected function createUserSchema(Connection $connection): void
    {
        $connection->executeStatement('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, created_at TEXT NOT NULL)');
        $connection->executeStatement('CREATE TABLE known_functions (user_id INTEGER NOT NULL, function_name TEXT NOT NULL, created_at TEXT NOT NULL, PRIMARY KEY(user_id, function_name), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)');
        $connection->executeStatement('CREATE TABLE known_concepts (user_id INTEGER NOT NULL, concept_slug TEXT NOT NULL, created_at TEXT NOT NULL, PRIMARY KEY(user_id, concept_slug), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)');
        $connection->executeStatement('CREATE TABLE known_frameworks (user_id INTEGER NOT NULL, framework_slug TEXT NOT NULL, created_at TEXT NOT NULL, PRIMARY KEY(user_id, framework_slug), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)');
    }
}
