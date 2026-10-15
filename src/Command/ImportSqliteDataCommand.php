<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use PDO;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Переносит данные прежних SQLite-баз в PostgreSQL. */
#[AsCommand(name: 'app:database:import-sqlite', description: 'Переносит данные из прежних SQLite-баз в PostgreSQL')]
final class ImportSqliteDataCommand extends Command
{
    public function __construct(private readonly Connection $database, private readonly string $projectDir)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('materials', null, InputOption::VALUE_REQUIRED, 'Путь к базе материалов', $this->projectDir.'/data/materials.sqlite')
            ->addOption('users', null, InputOption::VALUE_REQUIRED, 'Путь к базе пользователей', $this->projectDir.'/data/users.sqlite')
            ->addOption('skip-missing', null, InputOption::VALUE_NONE, 'Пропустить отсутствующие SQLite-файлы')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Повторить уже выполненный импорт');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $materialsPath = (string) $input->getOption('materials');
        $usersPath = (string) $input->getOption('users');
        $skipMissing = (bool) $input->getOption('skip-missing');
        if (!(bool) $input->getOption('force') && $this->database->fetchOne('SELECT 1 FROM data_imports WHERE name = :name', ['name' => 'sqlite-v1']) !== false) {
            $io->note('Данные из SQLite уже были импортированы. Для повторения используйте --force.');

            return Command::SUCCESS;
        }

        $sources = array_filter([
            'материалы' => $this->openSqlite($materialsPath, $skipMissing, $io),
            'пользователи' => $this->openSqlite($usersPath, $skipMissing, $io),
        ]);
        if ($sources === []) {
            $io->warning('SQLite-файлы для импорта не найдены.');

            return Command::SUCCESS;
        }

        $this->database->beginTransaction();
        try {
            $counts = [];
            if (isset($sources['материалы'])) {
                $counts = [...$counts, ...$this->importMaterials($sources['материалы'])];
            }
            if (isset($sources['пользователи'])) {
                $counts = [...$counts, ...$this->importUsers($sources['пользователи'])];
            }
            $this->database->executeStatement(
                'INSERT INTO data_imports (name, imported_at) VALUES (:name, :imported_at) ON CONFLICT (name) DO UPDATE SET imported_at = EXCLUDED.imported_at',
                ['name' => 'sqlite-v1', 'imported_at' => (new \DateTimeImmutable())->format(DATE_ATOM)],
            );
            $this->database->commit();
        } catch (\Throwable $exception) {
            $this->database->rollBack();
            throw $exception;
        }

        $io->success('Данные из SQLite перенесены в PostgreSQL.');
        $io->table(['Таблица', 'Обработано строк'], array_map(
            static fn (string $table, int $count): array => [$table, $count],
            array_keys($counts),
            array_values($counts),
        ));

        return Command::SUCCESS;
    }

    private function openSqlite(string $path, bool $skipMissing, SymfonyStyle $io): ?PDO
    {
        if (!is_file($path)) {
            if ($skipMissing) {
                $io->note(sprintf('Файл %s отсутствует и пропущен.', $path));

                return null;
            }

            throw new \RuntimeException(sprintf('SQLite-файл не найден: %s', $path));
        }

        return new PDO('sqlite:'.$path, options: [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    /** @return array<string, int> */
    private function importMaterials(PDO $source): array
    {
        $typeIds = [];
        foreach ($this->rows($source, 'SELECT code, title FROM material_types ORDER BY id') as $row) {
            $this->database->executeStatement(
                'INSERT INTO material_types (code, title) VALUES (:code, :title) ON CONFLICT (code) DO UPDATE SET title = EXCLUDED.title',
                $row,
            );
            $typeIds[$row['code']] = (int) $this->database->fetchOne('SELECT id FROM material_types WHERE code = :code', ['code' => $row['code']]);
        }

        $categoryIds = [];
        foreach ($this->rows($source, 'SELECT categories.id, material_types.code AS type_code, categories.code, categories.title, categories.position FROM categories JOIN material_types ON material_types.id = categories.type_id ORDER BY categories.id') as $row) {
            $typeId = $typeIds[$row['type_code']];
            $this->database->executeStatement(
                'INSERT INTO categories (type_id, code, title, position) VALUES (:type_id, :code, :title, :position) ON CONFLICT (type_id, code) DO UPDATE SET title = EXCLUDED.title, position = EXCLUDED.position',
                ['type_id' => $typeId, 'code' => $row['code'], 'title' => $row['title'], 'position' => $row['position']],
            );
            $categoryIds[(int) $row['id']] = (int) $this->database->fetchOne(
                'SELECT id FROM categories WHERE type_id = :type_id AND code = :code',
                ['type_id' => $typeId, 'code' => $row['code']],
            );
        }

        $materialIds = [];
        $materials = $this->rows($source, 'SELECT materials.*, material_types.code AS type_code FROM materials JOIN material_types ON material_types.id = materials.type_id ORDER BY materials.id');
        foreach ($materials as $row) {
            $typeId = $typeIds[$row['type_code']];
            $parameters = [
                'type_id' => $typeId,
                'category_id' => $row['category_id'] === null ? null : $categoryIds[(int) $row['category_id']],
                'title' => $row['title'], 'slug' => $row['slug'], 'definition' => $row['definition'],
                'short_description' => $row['short_description'], 'full_description' => $row['full_description'],
                'content_format' => $row['content_format'], 'source_url' => $row['source_url'],
                'position' => $row['position'], 'created_at' => $row['created_at'], 'updated_at' => $row['updated_at'],
            ];
            $this->database->executeStatement(
                'INSERT INTO materials (type_id, category_id, title, slug, definition, short_description, full_description, content_format, source_url, position, created_at, updated_at)
                 VALUES (:type_id, :category_id, :title, :slug, :definition, :short_description, :full_description, :content_format, :source_url, :position, :created_at, :updated_at)
                 ON CONFLICT (type_id, slug) DO UPDATE SET category_id = EXCLUDED.category_id, title = EXCLUDED.title, definition = EXCLUDED.definition, short_description = EXCLUDED.short_description, full_description = EXCLUDED.full_description, content_format = EXCLUDED.content_format, source_url = EXCLUDED.source_url, position = EXCLUDED.position, created_at = EXCLUDED.created_at, updated_at = EXCLUDED.updated_at',
                $parameters,
            );
            $materialIds[(int) $row['id']] = (int) $this->database->fetchOne(
                'SELECT id FROM materials WHERE type_id = :type_id AND slug = :slug',
                ['type_id' => $typeId, 'slug' => $row['slug']],
            );
        }

        $sectionIds = [];
        foreach ($materialIds as $targetMaterialId) {
            $this->database->executeStatement('DELETE FROM code_examples WHERE material_id = :material_id', ['material_id' => $targetMaterialId]);
            $this->database->executeStatement('DELETE FROM material_sections WHERE material_id = :material_id', ['material_id' => $targetMaterialId]);
        }
        $sections = $this->rows($source, 'SELECT * FROM material_sections ORDER BY id');
        foreach ($sections as $row) {
            $sectionIds[(int) $row['id']] = (int) $this->database->fetchOne(
                'INSERT INTO material_sections (material_id, title, slug, description, position) VALUES (:material_id, :title, :slug, :description, :position) RETURNING id',
                ['material_id' => $materialIds[(int) $row['material_id']], 'title' => $row['title'], 'slug' => $row['slug'], 'description' => $row['description'], 'position' => $row['position']],
            );
        }

        $examples = $this->rows($source, 'SELECT * FROM code_examples ORDER BY id');
        foreach ($examples as $row) {
            $this->database->executeStatement(
                'INSERT INTO code_examples (material_id, section_id, title, language, code, position) VALUES (:material_id, :section_id, :title, :language, :code, :position)',
                ['material_id' => $materialIds[(int) $row['material_id']], 'section_id' => $row['section_id'] === null ? null : $sectionIds[(int) $row['section_id']], 'title' => $row['title'], 'language' => $row['language'], 'code' => $row['code'], 'position' => $row['position']],
            );
        }

        $tasks = $this->tableExists($source, 'tasks') ? $this->rows($source, 'SELECT difficulty, title, description, starter_code, solution_code FROM tasks ORDER BY id') : [];
        foreach ($tasks as $row) {
            $this->database->executeStatement(
                'INSERT INTO tasks (difficulty, title, description, starter_code, solution_code) VALUES (:difficulty, :title, :description, :starter_code, :solution_code) ON CONFLICT (difficulty, title) DO UPDATE SET description = EXCLUDED.description, starter_code = EXCLUDED.starter_code, solution_code = EXCLUDED.solution_code',
                $row,
            );
        }

        $admins = $this->tableExists($source, 'admin_users') ? $this->rows($source, 'SELECT email, password_hash, roles, created_at, updated_at FROM admin_users ORDER BY id') : [];
        foreach ($admins as $row) {
            $this->database->executeStatement(
                'INSERT INTO admin_users (email, password_hash, roles, created_at, updated_at) VALUES (:email, :password_hash, CAST(:roles AS JSON), :created_at, :updated_at) ON CONFLICT (email) DO UPDATE SET password_hash = EXCLUDED.password_hash, roles = EXCLUDED.roles, created_at = EXCLUDED.created_at, updated_at = EXCLUDED.updated_at',
                $row,
            );
        }

        return ['material_types' => count($typeIds), 'categories' => count($categoryIds), 'materials' => count($materialIds), 'material_sections' => count($sections), 'code_examples' => count($examples), 'tasks' => count($tasks), 'admin_users' => count($admins)];
    }

    /** @return array<string, int> */
    private function importUsers(PDO $source): array
    {
        $userIds = [];
        $users = $this->rows($source, 'SELECT id, email, password_hash, created_at FROM users ORDER BY id');
        foreach ($users as $row) {
            $email = mb_strtolower(trim((string) $row['email']));
            $this->database->executeStatement(
                'INSERT INTO users (email, password_hash, created_at) VALUES (:email, :password_hash, :created_at) ON CONFLICT DO NOTHING',
                ['email' => $email, 'password_hash' => $row['password_hash'], 'created_at' => $row['created_at']],
            );
            $this->database->executeStatement(
                'UPDATE users SET password_hash = :password_hash, created_at = :created_at WHERE LOWER(email) = LOWER(:email)',
                ['email' => $email, 'password_hash' => $row['password_hash'], 'created_at' => $row['created_at']],
            );
            $userIds[(int) $row['id']] = (int) $this->database->fetchOne('SELECT id FROM users WHERE LOWER(email) = LOWER(:email)', ['email' => $email]);
        }

        $progressTables = ['known_functions' => 'function_name', 'known_concepts' => 'concept_slug', 'known_frameworks' => 'framework_slug'];
        $counts = ['users' => count($users)];
        foreach ($progressTables as $table => $itemColumn) {
            $rows = $this->tableExists($source, $table) ? $this->rows($source, sprintf('SELECT user_id, %s, created_at FROM %s', $itemColumn, $table)) : [];
            foreach ($rows as $row) {
                if (!isset($userIds[(int) $row['user_id']])) {
                    continue;
                }
                $this->database->executeStatement(
                    sprintf('INSERT INTO %s (user_id, %s, created_at) VALUES (:user_id, :item, :created_at) ON CONFLICT (user_id, %s) DO UPDATE SET created_at = EXCLUDED.created_at', $table, $itemColumn, $itemColumn),
                    ['user_id' => $userIds[(int) $row['user_id']], 'item' => $row[$itemColumn], 'created_at' => $row['created_at']],
                );
            }
            $counts[$table] = count($rows);
        }

        return $counts;
    }

    /** @return list<array<string, mixed>> */
    private function rows(PDO $source, string $sql): array
    {
        $rows = $source->query($sql)->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    private function tableExists(PDO $source, string $table): bool
    {
        $statement = $source->prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = :table");
        $statement->execute(['table' => $table]);

        return $statement->fetchColumn() !== false;
    }
}
