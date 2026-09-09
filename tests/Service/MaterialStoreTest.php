<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\MaterialStore;
use PHPUnit\Framework\TestCase;

/** Проверяет единое хранилище учебных материалов. */
final class MaterialStoreTest extends TestCase
{
    private string $databaseFile;

    protected function setUp(): void
    {
        $this->databaseFile = sys_get_temp_dir().'/php-recall-materials-'.bin2hex(random_bytes(8)).'.sqlite';
    }

    protected function tearDown(): void
    {
        if (is_file($this->databaseFile)) {
            unlink($this->databaseFile);
        }
    }

    public function testReadsFunctionCatalogWithoutDuplicates(): void
    {
        $store = new MaterialStore($this->databaseFile);
        $catalog = [
            'arrays' => ['title' => 'Массивы', 'functions' => ['array_map', 'array_filter']],
        ];

        foreach ($catalog['arrays']['functions'] as $position => $function) {
            $material = [
                'title' => $function,
                'slug' => $function,
                'definition' => '',
                'short_description' => '',
                'full_description' => '',
                'position' => $position,
            ];
            $store->saveMaterial('function', 'PHP-функции', 'arrays', 'Массивы', $material);
            $store->saveMaterial('function', 'PHP-функции', 'arrays', 'Массивы', $material);
        }

        self::assertSame($catalog, $store->functionCatalog());
        self::assertTrue($store->containsFunction('array_map'));
        self::assertFalse($store->containsFunction('strlen'));
    }

    public function testStoresFunctionDocumentationInSeparateFields(): void
    {
        $store = new MaterialStore($this->databaseFile);
        $store->saveMaterial('function', 'PHP-функции', 'arrays', 'Массивы', [
            'title' => 'array_map',
            'slug' => 'array_map',
            'definition' => '',
            'short_description' => '',
            'full_description' => '',
        ]);

        $store->saveFunctionDocumentation(
            'array_map',
            'array_map(?callable $callback, array $array, array ...$arrays): array',
            'Применяет callback к элементам массивов.',
            '<section><p>Полное описание.</p></section>',
            'https://www.php.net/manual/ru/function.array-map.php',
        );

        $documentation = $store->functionDocumentation('array_map');
        self::assertNotNull($documentation);
        self::assertSame('Применяет callback к элементам массивов.', $documentation['short_description']);
        self::assertStringContainsString('array_map', $documentation['definition']);
        self::assertSame('<section><p>Полное описание.</p></section>', $documentation['full_description']);
    }

    public function testStoresConceptWithOrderedCodeExamples(): void
    {
        $store = new MaterialStore($this->databaseFile);
        $materialId = $store->saveMaterial('concept', 'Концепции разработки', 'principles', 'Принципы', [
            'title' => 'SOLID',
            'slug' => 'solid',
            'definition' => 'Пять принципов объектно-ориентированного проектирования.',
            'short_description' => 'Помогает создавать поддерживаемый код.',
            'full_description' => '# SOLID',
            'content_format' => 'markdown',
        ]);
        $store->replaceCodeExamples($materialId, [
            ['title' => 'До', 'language' => 'php', 'code' => '<?php echo "до";'],
            ['title' => 'После', 'language' => 'php', 'code' => '<?php echo "после";'],
        ]);

        $material = $store->material('concept', 'solid');
        self::assertNotNull($material);
        self::assertSame('SOLID', $material['title']);
        self::assertSame('markdown', $material['content_format']);
        self::assertSame(['До', 'После'], array_column($material['code_examples'], 'title'));
    }

    public function testStoresIndependentlyExpandableConceptSections(): void
    {
        $store = new MaterialStore($this->databaseFile);
        $materialId = $store->saveMaterial('concept', 'Концепции разработки', 'principles', 'Принципы', [
            'title' => 'SOLID',
            'slug' => 'solid',
            'definition' => 'Пять принципов.',
            'short_description' => 'Краткое описание.',
            'full_description' => '<p>Полное объяснение.</p>',
        ]);
        $store->replaceSections($materialId, [
            [
                'title' => 'S — Single Responsibility',
                'slug' => 'srp',
                'description' => '<p>Одна причина для изменения.</p>',
                'examples' => [['title' => 'Пример', 'language' => 'php', 'code' => 'final class Service {}']],
            ],
            ['title' => 'O — Open/Closed', 'slug' => 'ocp', 'description' => '<p>Открыт для расширения.</p>'],
        ]);

        $material = $store->material('concept', 'solid');

        self::assertNotNull($material);
        self::assertSame(['srp', 'ocp'], array_column($material['sections'], 'slug'));
        self::assertSame('Пример', $material['sections'][0]['code_examples'][0]['title']);
        self::assertSame([], $material['sections'][1]['code_examples']);
    }

    public function testKeepsFrameworkCatalogSeparateFromConcepts(): void
    {
        $store = new MaterialStore($this->databaseFile);
        $store->saveMaterial('concept', 'Концепции разработки', 'architecture', 'Архитектура', [
            'title' => 'MVC',
            'slug' => 'mvc',
            'definition' => 'Архитектурный шаблон.',
            'short_description' => 'Разделяет ответственности.',
            'full_description' => '<p>Описание MVC.</p>',
        ]);
        $store->saveMaterial('framework', 'Фреймворки', 'laravel', 'Laravel', [
            'title' => 'Что такое Service Container?',
            'slug' => 'service-container',
            'definition' => 'Контейнер зависимостей.',
            'short_description' => 'Создаёт объекты приложения.',
            'full_description' => '<p>Описание контейнера.</p>',
        ]);

        self::assertSame(['architecture'], array_keys($store->conceptCatalog()));
        self::assertSame(['laravel'], array_keys($store->frameworkCatalog()));
        self::assertSame('service-container', $store->frameworkCatalog()['laravel']['materials'][0]['slug']);
    }
}
