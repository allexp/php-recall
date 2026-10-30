<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\AdminUser;
use App\Kernel;
use App\Tests\Support\ResetsDatabase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Проверяет смену пароля авторизованного администратора. */
final class ChangePasswordControllerTest extends WebTestCase
{
    use ResetsDatabase;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testRedirectsGuestToAdminLogin(): void
    {
        $client = static::createClient();
        $this->resetDatabase();

        $client->request('GET', '/admin/change-password');

        self::assertResponseRedirects('/admin/login');
    }

    public function testChangesPasswordForAuthorizedAdministrator(): void
    {
        $client = static::createClient();
        $entityManager = $this->resetDatabase();
        $admin = (new AdminUser())->setEmail('change-password@example.com');
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $admin->setPassword($passwordHasher->hashPassword($admin, 'current-password'));
        $entityManager->persist($admin);
        $entityManager->flush();
        $client->loginUser($admin, 'admin');

        $crawler = $client->request('GET', '/admin/change-password');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Сохранить новый пароль')->form([
            'current_password' => 'current-password',
            'new_password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/admin/change-password');
        $client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Пароль успешно изменён.');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $storedAdmin = $entityManager->getRepository(AdminUser::class)->findOneBy([
            'email' => 'change-password@example.com',
        ]);
        self::assertInstanceOf(AdminUser::class, $storedAdmin);
        self::assertTrue($passwordHasher->isPasswordValid($storedAdmin, 'new-secure-password'));
        self::assertFalse($passwordHasher->isPasswordValid($storedAdmin, 'current-password'));
    }

    public function testRejectsInvalidCurrentPasswordAndMismatchedConfirmation(): void
    {
        $client = static::createClient();
        $entityManager = $this->resetDatabase();
        $admin = (new AdminUser())->setEmail('invalid-password@example.com');
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $originalHash = $passwordHasher->hashPassword($admin, 'current-password');
        $admin->setPassword($originalHash);
        $entityManager->persist($admin);
        $entityManager->flush();
        $client->loginUser($admin, 'admin');

        $crawler = $client->request('GET', '/admin/change-password');
        $form = $crawler->selectButton('Сохранить новый пароль')->form([
            'current_password' => 'incorrect-password',
            'new_password' => 'short',
            'password_confirmation' => 'different-password',
        ]);
        $client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Текущий пароль указан неверно.');
        self::assertSelectorTextContains('body', 'Новый пароль должен содержать не менее 12 символов.');
        self::assertSelectorTextContains('body', 'Новый пароль и его подтверждение не совпадают.');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $storedAdmin = $entityManager->getRepository(AdminUser::class)->findOneBy([
            'email' => 'invalid-password@example.com',
        ]);
        self::assertInstanceOf(AdminUser::class, $storedAdmin);
        self::assertSame($originalHash, $storedAdmin->getPassword());
    }
}
