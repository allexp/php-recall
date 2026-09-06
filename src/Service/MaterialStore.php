<?php

declare(strict_types=1);

namespace App\Service;

use PDO;
use RuntimeException;

/** Хранит категории, учебные материалы и примеры кода в SQLite. */
final class MaterialStore
{
    private ?PDO $connection = null;

    public function __construct(private readonly string $databaseFile)
    {
    }

    /**
     * Синхронизирует категории и функции из конфигурации с базой данных.
     *
     * @param array<string, array{title: string, functions: list<string>}> $catalog
     */
    public function synchroniseFunctionCatalog(array $catalog): void
    {
        $connection = $this->connection();
        $connection->beginTransaction();

        try {
            $typeId = $this->ensureType('function', 'PHP-функции');
            $this->ensureType('concept', 'Концепции разработки');
            $categoryPosition = 0;
            foreach ($catalog as $categoryCode => $category) {
                $categoryId = $this->ensureCategory(
                    $typeId,
                    $categoryCode,
                    $category['title'],
                    $categoryPosition,
                );
                foreach ($category['functions'] as $position => $function) {
                    $statement = $connection->prepare(
                        'INSERT INTO materials (
                            type_id, category_id, title, slug, definition, short_description,
                            full_description, content_format, position, created_at, updated_at
                        ) VALUES (
                            :type_id, :category_id, :title, :slug, :definition, :short_description,
                            :full_description, :content_format, :position, :created_at, :updated_at
                        )
                        ON CONFLICT(type_id, slug) DO UPDATE SET
                            category_id = excluded.category_id,
                            title = excluded.title,
                            position = excluded.position,
                            updated_at = excluded.updated_at'
                    );
                    $now = $this->now();
                    $statement->execute([
                        'type_id' => $typeId,
                        'category_id' => $categoryId,
                        'title' => $function,
                        'slug' => $function,
                        'definition' => '',
                        'short_description' => '',
                        'full_description' => '',
                        'content_format' => 'html',
                        'position' => $position,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                ++$categoryPosition;
            }

            $connection->commit();
        } catch (\Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    /** Возвращает каталог функций в формате публичного API. */
    public function functionCatalog(): array
    {
        $rows = $this->connection()->query(
            "SELECT categories.code, categories.title, materials.slug
             FROM materials
             JOIN material_types ON material_types.id = materials.type_id
             JOIN categories ON categories.id = materials.category_id
             WHERE material_types.code = 'function'
             ORDER BY categories.position, categories.id, materials.position, materials.id"
        )->fetchAll();

        $catalog = [];
        foreach ($rows as $row) {
            $code = (string) $row['code'];
            $catalog[$code] ??= ['title' => (string) $row['title'], 'functions' => []];
            $catalog[$code]['functions'][] = (string) $row['slug'];
        }

        return $catalog;
    }

    /** Проверяет наличие функции в каталоге материалов. */
    public function containsFunction(string $function): bool
    {
        $statement = $this->connection()->prepare(
            "SELECT 1 FROM materials
             JOIN material_types ON material_types.id = materials.type_id
             WHERE material_types.code = 'function' AND materials.slug = :slug"
        );
        $statement->execute(['slug' => $function]);

        return $statement->fetchColumn() !== false;
    }

    /** Возвращает имена всех функций каталога. */
    public function functionNames(): array
    {
        $statement = $this->connection()->query(
            "SELECT materials.slug FROM materials
             JOIN material_types ON material_types.id = materials.type_id
             WHERE material_types.code = 'function'
             ORDER BY materials.slug"
        );

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Создаёт или обновляет учебный материал и возвращает его идентификатор.
     *
     * @param array{
     *     title: string,
     *     slug: string,
     *     definition: string,
     *     short_description: string,
     *     full_description: string,
     *     content_format?: string,
     *     source_url?: string|null
     * } $material
     */
    public function saveMaterial(
        string $typeCode,
        string $typeTitle,
        string $categoryCode,
        string $categoryTitle,
        array $material,
    ): int {
        $typeId = $this->ensureType($typeCode, $typeTitle);
        $categoryId = $this->ensureCategory($typeId, $categoryCode, $categoryTitle);
        $now = $this->now();
        $statement = $this->connection()->prepare(
            'INSERT INTO materials (
                type_id, category_id, title, slug, definition, short_description,
                full_description, content_format, source_url, created_at, updated_at
            ) VALUES (
                :type_id, :category_id, :title, :slug, :definition, :short_description,
                :full_description, :content_format, :source_url, :created_at, :updated_at
            )
            ON CONFLICT(type_id, slug) DO UPDATE SET
                category_id = excluded.category_id,
                title = excluded.title,
                definition = excluded.definition,
                short_description = excluded.short_description,
                full_description = excluded.full_description,
                content_format = excluded.content_format,
                source_url = excluded.source_url,
                updated_at = excluded.updated_at'
        );
        $statement->execute([
            'type_id' => $typeId,
            'category_id' => $categoryId,
            'title' => $material['title'],
            'slug' => $material['slug'],
            'definition' => $material['definition'],
            'short_description' => $material['short_description'],
            'full_description' => $material['full_description'],
            'content_format' => $material['content_format'] ?? 'html',
            'source_url' => $material['source_url'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $statement = $this->connection()->prepare(
            'SELECT id FROM materials WHERE type_id = :type_id AND slug = :slug'
        );
        $statement->execute(['type_id' => $typeId, 'slug' => $material['slug']]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Заменяет примеры кода материала, сохраняя порядок переданного списка.
     *
     * @param list<array{title?: string, language: string, code: string}> $examples
     */
    public function replaceCodeExamples(int $materialId, array $examples): void
    {
        $connection = $this->connection();
        $connection->beginTransaction();

        try {
            $statement = $connection->prepare('DELETE FROM code_examples WHERE material_id = :material_id');
            $statement->execute(['material_id' => $materialId]);
            $statement = $connection->prepare(
                'INSERT INTO code_examples (material_id, title, language, code, position)
                 VALUES (:material_id, :title, :language, :code, :position)'
            );
            foreach ($examples as $position => $example) {
                $statement->execute([
                    'material_id' => $materialId,
                    'title' => $example['title'] ?? '',
                    'language' => $example['language'],
                    'code' => $example['code'],
                    'position' => $position,
                ]);
            }
            $connection->commit();
        } catch (\Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    /** Возвращает материал вместе с упорядоченными примерами кода. */
    public function material(string $typeCode, string $slug): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT materials.*, categories.code AS category_code, categories.title AS category_title
             FROM materials
             JOIN material_types ON material_types.id = materials.type_id
             LEFT JOIN categories ON categories.id = materials.category_id
             WHERE material_types.code = :type_code AND materials.slug = :slug'
        );
        $statement->execute(['type_code' => $typeCode, 'slug' => $slug]);
        $material = $statement->fetch();
        if (!is_array($material)) {
            return null;
        }

        $statement = $this->connection()->prepare(
            'SELECT title, language, code, position FROM code_examples
             WHERE material_id = :material_id ORDER BY position, id'
        );
        $statement->execute(['material_id' => $material['id']]);
        $material['code_examples'] = $statement->fetchAll();

        return $material;
    }

    /** Возвращает документацию функции или null, если она ещё не загружена. */
    public function functionDocumentation(string $function): ?array
    {
        $statement = $this->connection()->prepare(
            "SELECT definition, short_description, full_description, content_format, source_url
             FROM materials
             JOIN material_types ON material_types.id = materials.type_id
             WHERE material_types.code = 'function' AND materials.slug = :slug"
        );
        $statement->execute(['slug' => $function]);
        $material = $statement->fetch();

        if (!is_array($material) || $material['short_description'] === '' || $material['full_description'] === '') {
            return null;
        }

        return $material;
    }

    /** Сохраняет загруженную документацию существующей функции. */
    public function saveFunctionDocumentation(
        string $function,
        string $definition,
        string $shortDescription,
        string $fullDescription,
        string $sourceUrl,
    ): void {
        $statement = $this->connection()->prepare(
            "UPDATE materials SET
                definition = :definition,
                short_description = :short_description,
                full_description = :full_description,
                content_format = 'html',
                source_url = :source_url,
                updated_at = :updated_at
             WHERE type_id = (SELECT id FROM material_types WHERE code = 'function')
               AND slug = :slug"
        );
        $statement->execute([
            'definition' => $definition,
            'short_description' => $shortDescription,
            'full_description' => $fullDescription,
            'source_url' => $sourceUrl,
            'updated_at' => $this->now(),
            'slug' => $function,
        ]);

        if ($statement->rowCount() === 0 && !$this->containsFunction($function)) {
            throw new RuntimeException('Нельзя сохранить документацию функции вне каталога.');
        }
    }

    private function ensureType(string $code, string $title): int
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO material_types (code, title) VALUES (:code, :title)
             ON CONFLICT(code) DO UPDATE SET title = excluded.title'
        );
        $statement->execute(['code' => $code, 'title' => $title]);

        $statement = $this->connection()->prepare('SELECT id FROM material_types WHERE code = :code');
        $statement->execute(['code' => $code]);

        return (int) $statement->fetchColumn();
    }

    private function ensureCategory(int $typeId, string $code, string $title, ?int $position = null): int
    {
        $updatePosition = $position !== null;
        $statement = $this->connection()->prepare(
            'INSERT INTO categories (type_id, code, title, position) VALUES (:type_id, :code, :title, :position)
             ON CONFLICT(type_id, code) DO UPDATE SET
                title = excluded.title,
                position = CASE WHEN :update_position = 1 THEN excluded.position ELSE categories.position END'
        );
        $statement->execute([
            'type_id' => $typeId,
            'code' => $code,
            'title' => $title,
            'position' => $position ?? $this->nextCategoryPosition($typeId),
            'update_position' => $updatePosition ? 1 : 0,
        ]);

        $statement = $this->connection()->prepare(
            'SELECT id FROM categories WHERE type_id = :type_id AND code = :code'
        );
        $statement->execute(['type_id' => $typeId, 'code' => $code]);

        return (int) $statement->fetchColumn();
    }

    private function nextCategoryPosition(int $typeId): int
    {
        $statement = $this->connection()->prepare(
            'SELECT COALESCE(MAX(position), -1) + 1 FROM categories WHERE type_id = :type_id'
        );
        $statement->execute(['type_id' => $typeId]);

        return (int) $statement->fetchColumn();
    }

    private function connection(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $directory = dirname($this->databaseFile);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Не удалось создать каталог базы материалов.');
        }

        $this->connection = new PDO('sqlite:'.$this->databaseFile, options: [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->connection->exec('PRAGMA foreign_keys = ON');
        $this->createSchema();

        return $this->connection;
    }

    private function createSchema(): void
    {
        $this->connection?->exec(
            "CREATE TABLE IF NOT EXISTS material_types (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT NOT NULL UNIQUE,
                title TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type_id INTEGER NOT NULL,
                code TEXT NOT NULL,
                title TEXT NOT NULL,
                position INTEGER NOT NULL DEFAULT 0,
                UNIQUE (type_id, code),
                FOREIGN KEY (type_id) REFERENCES material_types(id) ON DELETE CASCADE
            );
            CREATE TABLE IF NOT EXISTS materials (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type_id INTEGER NOT NULL,
                category_id INTEGER,
                title TEXT NOT NULL,
                slug TEXT NOT NULL,
                definition TEXT NOT NULL DEFAULT '',
                short_description TEXT NOT NULL DEFAULT '',
                full_description TEXT NOT NULL DEFAULT '',
                content_format TEXT NOT NULL DEFAULT 'html' CHECK (content_format IN ('html', 'markdown', 'text')),
                source_url TEXT,
                position INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                UNIQUE (type_id, slug),
                FOREIGN KEY (type_id) REFERENCES material_types(id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
            );
            CREATE TABLE IF NOT EXISTS code_examples (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                material_id INTEGER NOT NULL,
                title TEXT NOT NULL DEFAULT '',
                language TEXT NOT NULL,
                code TEXT NOT NULL,
                position INTEGER NOT NULL DEFAULT 0,
                FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
            );
            CREATE INDEX IF NOT EXISTS idx_categories_type ON categories(type_id, position);
            CREATE INDEX IF NOT EXISTS idx_materials_category ON materials(category_id);
            CREATE INDEX IF NOT EXISTS idx_materials_type_title ON materials(type_id, title);
            CREATE INDEX IF NOT EXISTS idx_code_examples_material ON code_examples(material_id, position);"
        );
        $columns = $this->connection?->query('PRAGMA table_info(materials)')->fetchAll(PDO::FETCH_COLUMN, 1) ?? [];
        if (!in_array('position', $columns, true)) {
            $this->connection?->exec('ALTER TABLE materials ADD COLUMN position INTEGER NOT NULL DEFAULT 0');
        }
    }

    private function now(): string
    {
        return (new \DateTimeImmutable())->format(DATE_ATOM);
    }
}
