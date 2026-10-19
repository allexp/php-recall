<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;
use PDO;

/** Хранит категории, учебные материалы и примеры кода в основной базе данных. */
final class MaterialStore
{
    private ?PDO $pdo = null;

    public function __construct(private readonly Connection $database)
    {
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

    /** Возвращает каталог концепций, сгруппированный по категориям. */
    public function conceptCatalog(): array
    {
        return $this->materialCatalog('concept');
    }

    /** Возвращает каталог фреймворков, сгруппированный по категориям. */
    public function frameworkCatalog(): array
    {
        return $this->materialCatalog('framework');
    }

    /** Возвращает типы материалов для построения публичного меню. */
    public function materialTypes(): array
    {
        $rows = $this->connection()->query(
            'SELECT code, title FROM material_types ORDER BY title, id'
        )->fetchAll();

        return array_map(static fn (array $row): array => [
            'code' => (string) $row['code'],
            'title' => (string) $row['title'],
        ], $rows);
    }

    /** Возвращает каталог материалов указанного типа, сгруппированный по категориям. */
    public function materialCatalog(string $typeCode): array
    {
        $statement = $this->connection()->prepare(
            "SELECT categories.code, categories.title AS category_title,
                    materials.title, materials.slug, materials.short_description
             FROM materials
             JOIN material_types ON material_types.id = materials.type_id
             JOIN categories ON categories.id = materials.category_id
             WHERE material_types.code = :type_code
             ORDER BY categories.position, categories.id, materials.position, materials.id"
        );
        $statement->execute(['type_code' => $typeCode]);
        $rows = $statement->fetchAll();

        $catalog = [];
        foreach ($rows as $row) {
            $code = (string) $row['code'];
            $catalog[$code] ??= ['title' => (string) $row['category_title'], 'materials' => []];
            $catalog[$code]['materials'][] = [
                'title' => (string) $row['title'],
                'slug' => (string) $row['slug'],
                'short_description' => (string) $row['short_description'],
            ];
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
     *     source_url?: string|null,
     *     position?: int
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
                full_description, content_format, source_url, position, created_at, updated_at
            ) VALUES (
                :type_id, :category_id, :title, :slug, :definition, :short_description,
                :full_description, :content_format, :source_url, :position, :created_at, :updated_at
            )
            ON CONFLICT(type_id, slug) DO UPDATE SET
                category_id = excluded.category_id,
                title = excluded.title,
                definition = excluded.definition,
                short_description = excluded.short_description,
                full_description = excluded.full_description,
                content_format = excluded.content_format,
                source_url = excluded.source_url,
                position = excluded.position,
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
            'position' => $material['position'] ?? 0,
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

    /**
     * Заменяет подсекции материала и связанные с ними примеры кода.
     *
     * @param list<array{title: string, slug: string, description: string, examples?: list<array{title?: string, language: string, code: string}>}> $sections
     */
    public function replaceSections(int $materialId, array $sections): void
    {
        $connection = $this->connection();
        $connection->beginTransaction();

        try {
            $statement = $connection->prepare('DELETE FROM material_sections WHERE material_id = :material_id');
            $statement->execute(['material_id' => $materialId]);
            $sectionStatement = $connection->prepare(
                'INSERT INTO material_sections (material_id, title, slug, description, position)
                 VALUES (:material_id, :title, :slug, :description, :position)
                 RETURNING id'
            );
            $exampleStatement = $connection->prepare(
                'INSERT INTO code_examples (material_id, section_id, title, language, code, position)
                 VALUES (:material_id, :section_id, :title, :language, :code, :position)'
            );
            foreach ($sections as $position => $section) {
                $sectionStatement->execute([
                    'material_id' => $materialId,
                    'title' => $section['title'],
                    'slug' => $section['slug'],
                    'description' => $section['description'],
                    'position' => $position,
                ]);
                $sectionId = (int) $sectionStatement->fetchColumn();
                $sectionStatement->closeCursor();
                foreach ($section['examples'] ?? [] as $examplePosition => $example) {
                    $exampleStatement->execute([
                        'material_id' => $materialId,
                        'section_id' => $sectionId,
                        'title' => $example['title'] ?? '',
                        'language' => $example['language'],
                        'code' => $example['code'],
                        'position' => $examplePosition,
                    ]);
                }
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

        $statement = $this->connection()->prepare(
            'SELECT id, title, slug, description, position FROM material_sections
             WHERE material_id = :material_id ORDER BY position, id'
        );
        $statement->execute(['material_id' => $material['id']]);
        $material['sections'] = $statement->fetchAll();
        $exampleStatement = $this->connection()->prepare(
            'SELECT title, language, code, position FROM code_examples
             WHERE section_id = :section_id ORDER BY position, id'
        );
        foreach ($material['sections'] as &$section) {
            $exampleStatement->execute(['section_id' => $section['id']]);
            $section['code_examples'] = $exampleStatement->fetchAll();
            unset($section['id']);
        }
        unset($section);

        return $material;
    }

    /** Удаляет материал указанного типа, если он существует. */
    public function deleteMaterial(string $typeCode, string $slug): void
    {
        $statement = $this->connection()->prepare(
            'DELETE FROM materials
             WHERE type_id = (SELECT id FROM material_types WHERE code = :type_code)
               AND slug = :slug'
        );
        $statement->execute(['type_code' => $typeCode, 'slug' => $slug]);
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
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $connection = $this->database->getNativeConnection();
        if (!$connection instanceof PDO) {
            throw new \LogicException('Для работы хранилища требуется PDO-подключение.');
        }

        $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $connection->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
        $this->pdo = $connection;

        return $this->pdo;
    }

    private function now(): string
    {
        return (new \DateTimeImmutable())->format(DATE_ATOM);
    }
}
