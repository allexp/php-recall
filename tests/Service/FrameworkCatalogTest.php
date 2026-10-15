<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\FrameworkCatalog;
use App\Service\MaterialStore;
use App\Tests\Support\CreatesTestDatabase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/** Проверяет каталог учебных карточек по Laravel. */
final class FrameworkCatalogTest extends TestCase
{
    use CreatesTestDatabase;

    private Connection $database;

    protected function setUp(): void
    {
        $this->database = $this->createTestDatabase();
        $this->createMaterialSchema($this->database);
    }

    public function testReadsLaravelCardsFromDatabase(): void
    {
        $store = new MaterialStore($this->database);
        $store->saveMaterial('framework', 'Фреймворки', 'laravel', 'Laravel', [
            'title' => 'Что такое Service Container?',
            'slug' => 'service-container',
            'definition' => 'Контейнер зависимостей.',
            'short_description' => 'Создаёт объекты приложения.',
            'full_description' => '<p>Описание контейнера.</p>',
        ]);
        $catalog = new FrameworkCatalog($store);

        self::assertArrayHasKey('laravel', $catalog->all());
        self::assertCount(1, $catalog->all()['laravel']['materials']);
        self::assertStringEndsWith('?', $catalog->all()['laravel']['materials'][0]['title']);

        $card = $catalog->get('service-container');
        self::assertNotNull($card);
        self::assertSame('Laravel', $card['category_title']);
        self::assertStringContainsString('Service Container', $card['title']);
        self::assertNotSame('', $card['full_description']);
    }
}
