<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Проверяет отдельную форму входа администратора. */
final class AdminSecurityControllerTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testLoginPageUsesEasyAdminTemplate(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', 'PHP Recall — администрирование');
        self::assertCount(1, $crawler->filter('form input[name="_username"]'));
        self::assertCount(1, $crawler->filter('form input[name="_password"]'));
        self::assertCount(1, $crawler->filter('form input[name="_csrf_token"]'));
    }

    public function testRejectsUnknownAdministrator(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/login');
        $form = $crawler->selectButton('Войти')->form([
            '_username' => 'missing-admin@example.com',
            '_password' => 'incorrect-password',
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/admin/login');
        $client->followRedirect();
        self::assertSelectorExists('.alert-danger');
    }
}
