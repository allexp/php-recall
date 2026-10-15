<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\ImportSqliteDataCommand;
use App\Tests\Support\CreatesTestDatabase;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/** Проверяет перенос данных из прежних SQLite-баз. */
final class ImportSqliteDataCommandTest extends TestCase
{
    use CreatesTestDatabase;

    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testImportsBothDatabasesOnlyOnce(): void
    {
        $materialsFile = $this->temporaryDatabase('materials');
        $usersFile = $this->temporaryDatabase('users');
        $this->createMaterialSource($materialsFile);
        $this->createUserSource($usersFile);

        $destination = $this->createTestDatabase();
        $this->createMaterialSchema($destination);
        $this->createUserSchema($destination);
        $destination->executeStatement("CREATE TABLE tasks (id INTEGER PRIMARY KEY AUTOINCREMENT, difficulty TEXT NOT NULL, title TEXT NOT NULL, description TEXT NOT NULL, starter_code TEXT NOT NULL, solution_code TEXT NOT NULL, UNIQUE(difficulty, title))");
        $destination->executeStatement('CREATE TABLE admin_users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, roles TEXT NOT NULL, created_at TEXT NOT NULL, updated_at TEXT NOT NULL)');
        $destination->executeStatement('CREATE TABLE data_imports (name TEXT PRIMARY KEY, imported_at TEXT NOT NULL)');

        $tester = new CommandTester(new ImportSqliteDataCommand($destination, dirname($materialsFile)));
        self::assertSame(0, $tester->execute(['--materials' => $materialsFile, '--users' => $usersFile]));
        self::assertSame(1, (int) $destination->fetchOne('SELECT COUNT(*) FROM materials'));
        self::assertSame(1, (int) $destination->fetchOne('SELECT COUNT(*) FROM tasks'));
        self::assertSame(1, (int) $destination->fetchOne('SELECT COUNT(*) FROM users'));
        self::assertSame(1, (int) $destination->fetchOne('SELECT COUNT(*) FROM known_functions'));

        self::assertSame(0, $tester->execute(['--materials' => $materialsFile, '--users' => $usersFile]));
        self::assertStringContainsString('уже были импортированы', $tester->getDisplay());
        self::assertSame(1, (int) $destination->fetchOne('SELECT COUNT(*) FROM materials'));
    }

    private function temporaryDatabase(string $name): string
    {
        $file = sys_get_temp_dir().'/php-recall-'.$name.'-'.bin2hex(random_bytes(8)).'.sqlite';
        $this->files[] = $file;

        return $file;
    }

    private function createMaterialSource(string $file): void
    {
        $source = new PDO('sqlite:'.$file);
        $source->exec("CREATE TABLE material_types (id INTEGER PRIMARY KEY, code TEXT, title TEXT);
            CREATE TABLE categories (id INTEGER PRIMARY KEY, type_id INTEGER, code TEXT, title TEXT, position INTEGER);
            CREATE TABLE materials (id INTEGER PRIMARY KEY, type_id INTEGER, category_id INTEGER, title TEXT, slug TEXT, definition TEXT, short_description TEXT, full_description TEXT, content_format TEXT, source_url TEXT, position INTEGER, created_at TEXT, updated_at TEXT);
            CREATE TABLE material_sections (id INTEGER PRIMARY KEY, material_id INTEGER, title TEXT, slug TEXT, description TEXT, position INTEGER);
            CREATE TABLE code_examples (id INTEGER PRIMARY KEY, material_id INTEGER, section_id INTEGER, title TEXT, language TEXT, code TEXT, position INTEGER);
            CREATE TABLE tasks (id INTEGER PRIMARY KEY, difficulty TEXT, title TEXT, description TEXT, starter_code TEXT, solution_code TEXT);
            INSERT INTO material_types VALUES (1, 'function', 'PHP-функции');
            INSERT INTO categories VALUES (1, 1, 'arrays', 'Массивы', 0);
            INSERT INTO materials VALUES (1, 1, 1, 'array_map', 'array_map', '', '', '', 'html', NULL, 0, '2026-01-01', '2026-01-01');
            INSERT INTO tasks VALUES (1, 'easy', 'Тестовая задача', 'Описание', '<?php', '<?php echo 1;');");
    }

    private function createUserSource(string $file): void
    {
        $source = new PDO('sqlite:'.$file);
        $source->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT, password_hash TEXT, created_at TEXT);
            CREATE TABLE known_functions (user_id INTEGER, function_name TEXT, created_at TEXT);
            CREATE TABLE known_concepts (user_id INTEGER, concept_slug TEXT, created_at TEXT);
            CREATE TABLE known_frameworks (user_id INTEGER, framework_slug TEXT, created_at TEXT);
            INSERT INTO users VALUES (1, 'student@example.com', 'hash', '2026-01-01');
            INSERT INTO known_functions VALUES (1, 'array_map', '2026-01-01');");
    }
}
