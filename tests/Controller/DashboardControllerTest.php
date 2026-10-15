<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\AdminUser;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Проверяет доступ и стандартную оболочку EasyAdmin. */
final class DashboardControllerTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testRedirectsGuestToSeparateAdminLogin(): void
    {
        $client = static::createClient();

        $client->request('GET', '/admin');

        self::assertResponseRedirects('/admin/login');
    }

    public function testRendersDashboardForAdministrator(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $admin = (new AdminUser())
            ->setEmail('dashboard-test@example.com')
            ->setPassword('unused-test-hash');
        $entityManager->persist($admin);
        $entityManager->flush();

        try {
            $client->loginUser($admin, 'admin');
            $client->request('GET', '/admin');

            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('.logo', 'PHP Recall');
            self::assertSelectorTextContains('.ea-sidebar', 'Главная');
            self::assertSelectorTextContains('.ea-sidebar', 'Управление материалами');
            self::assertSelectorTextContains('.ea-sidebar', 'Перейти на сайт');
            self::assertSelectorTextContains('.ea-sidebar', 'Выйти');
        } finally {
            $entityManager = static::getContainer()->get(EntityManagerInterface::class);
            $storedAdmin = $entityManager->getRepository(AdminUser::class)->findOneBy([
                'email' => 'dashboard-test@example.com',
            ]);

            if ($storedAdmin instanceof AdminUser) {
                $entityManager->remove($storedAdmin);
                $entityManager->flush();
            }
        }
    }
}
