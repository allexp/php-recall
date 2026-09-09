<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\FrameworkCatalog;
use App\Service\MaterialStore;
use PHPUnit\Framework\TestCase;

/** Проверяет каталог учебных карточек по Laravel. */
final class FrameworkCatalogTest extends TestCase
{
    private string $databaseFile;

    protected function setUp(): void
    {
        $this->databaseFile = sys_get_temp_dir().'/php-recall-frameworks-'.bin2hex(random_bytes(8)).'.sqlite';
    }

    protected function tearDown(): void
    {
        if (is_file($this->databaseFile)) {
            unlink($this->databaseFile);
        }
    }

    public function testReadsLaravelCardsFromDatabase(): void
    {
        $store = new MaterialStore($this->databaseFile);
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
