<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

/** Настраивает основной интерфейс административной части. */
#[AdminDashboard(
    routePath: '/admin',
    routeName: 'admin',
    routeOptions: ['methods' => ['GET']],
)]
final class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('PHP Recall')
            ->setFaviconPath('favicon.png');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Главная', 'fa fa-house');
        yield MenuItem::section('Управление материалами');
        yield MenuItem::linkTo(MaterialCrudController::class, 'Материалы', 'fa fa-file-lines');
        yield MenuItem::linkTo(MaterialTypeCrudController::class, 'Типы материалов', 'fa fa-layer-group');
        yield MenuItem::linkTo(CategoryCrudController::class, 'Категории', 'fa fa-folder');
        yield MenuItem::linkTo(MaterialSectionCrudController::class, 'Разделы материалов', 'fa fa-list');
        yield MenuItem::linkTo(CodeExampleCrudController::class, 'Примеры кода', 'fa fa-code');
        yield MenuItem::section();
        yield MenuItem::linkToRoute('Сменить пароль', 'fa fa-key', 'admin_change_password');
        yield MenuItem::linkToRoute('Перейти на сайт', 'fa fa-arrow-up-right-from-square', 'app_home');
        yield MenuItem::linkToLogout('Выйти', 'fa fa-right-from-bracket');
    }
}
