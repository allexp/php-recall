<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\Admin\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use PHPUnit\Framework\TestCase;

/** Проверяет настройки списка категорий в административной панели. */
final class CategoryCrudControllerConfigurationTest extends TestCase
{
    public function testProvidesTypeFilter(): void
    {
        $filters = (new CategoryCrudController())
            ->configureFilters(Filters::new())
            ->getAsDto()
            ->all();

        self::assertSame(['type'], array_keys($filters));
        self::assertInstanceOf(EntityFilter::class, $filters['type']);
        self::assertSame('Тип', $filters['type']->getAsDto()->getLabel());
    }
}
