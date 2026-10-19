<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\Admin\MaterialCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use PHPUnit\Framework\TestCase;

/** Проверяет настройки списка материалов в административной панели. */
final class MaterialCrudControllerConfigurationTest extends TestCase
{
    public function testSearchesByTitleAndProvidesTypeAndCategoryFilters(): void
    {
        $controller = new MaterialCrudController();

        $crud = $controller->configureCrud(Crud::new())->getAsDto();
        self::assertSame(['title'], $crud->getSearchFields());

        $filters = $controller->configureFilters(Filters::new())->getAsDto()->all();
        self::assertSame(['type', 'category'], array_keys($filters));
        self::assertContainsOnlyInstancesOf(EntityFilter::class, $filters);
        self::assertSame('Тип', $filters['type']->getAsDto()->getLabel());
        self::assertSame('Категория', $filters['category']->getAsDto()->getLabel());
    }
}
