<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Material;
use App\Entity\MaterialType;
use App\Kernel;
use App\Tests\Support\ResetsDatabase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Проверяет публикацию добавленных через админку типов материалов. */
final class MaterialTypeMenuControllerTest extends WebTestCase
{
    use ResetsDatabase;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testAddedTypeAppearsInMenuAndIsAvailableThroughApi(): void
    {
        $client = static::createClient();
        $entityManager = $this->resetDatabase();
        $type = (new MaterialType())->setCode('article')->setTitle('Статьи');
        $category = (new Category())->setType($type)->setCode('architecture')->setTitle('Архитектура');
        $material = (new Material())
            ->setType($type)
            ->setCategory($category)
            ->setTitle('Гексагональная архитектура')
            ->setSlug('hexagonal_architecture')
            ->setDefinition('Архитектурный подход.')
            ->setShortDescription('Изолирует бизнес-логику.')
            ->setFullDescription('<p>Полное описание.</p>');
        $entityManager->persist($type);
        $entityManager->persist($category);
        $entityManager->persist($material);
        $entityManager->flush();

        $client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-material-type="article"]', 'Статьи');

        $client->request('GET', '/api/material-types/article');
        self::assertResponseIsSuccessful();
        self::assertSame(
            'Архитектура',
            json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['architecture']['title'],
        );

        $client->request('GET', '/api/material-types/article/hexagonal_architecture');
        self::assertResponseIsSuccessful();
        self::assertSame(
            'Гексагональная архитектура',
            json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['title'],
        );
    }
}
