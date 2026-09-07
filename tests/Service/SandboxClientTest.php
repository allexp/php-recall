<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\SandboxClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/** Проверяет клиент внутреннего сервиса песочницы. */
final class SandboxClientTest extends TestCase
{
    public function testAddsOpeningTagAndReturnsRunnerResult(): void
    {
        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options): MockResponse {
            self::assertSame('POST', $method);
            self::assertSame('http://runner:8080/run', $url);
            self::assertContains('Authorization: Bearer secret', $options['headers']);
            self::assertStringContainsString('<?php', (string) $options['body']);

            return new MockResponse('{"output":"42","exit_code":0,"timed_out":false}');
        });
        $client = new SandboxClient($httpClient, 'http://runner:8080', 'secret');

        self::assertSame(['output' => '42', 'exit_code' => 0, 'timed_out' => false], $client->run('echo 42;'));
    }

    public function testRejectsEmptyAndOversizedCode(): void
    {
        $client = new SandboxClient(new MockHttpClient(), 'http://runner:8080', 'secret');

        try {
            $client->run('   ');
            self::fail('Пустой код должен быть отклонён.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Введите PHP-код для запуска.', $exception->getMessage());
        }

        $this->expectException(\InvalidArgumentException::class);
        $client->run(str_repeat('x', 20_001));
    }
}
