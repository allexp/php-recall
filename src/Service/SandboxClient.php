<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/** Передаёт PHP-код внутреннему сервису изолированного выполнения. */
final class SandboxClient
{
    private const MAX_CODE_BYTES = 20_000;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $runnerUrl,
        private readonly string $runnerToken,
    ) {
    }

    /** @return array{output: string, exit_code: int|null, timed_out: bool} */
    public function run(string $code): array
    {
        if (trim($code) === '') {
            throw new \InvalidArgumentException('Введите PHP-код для запуска.');
        }
        if (strlen($code) > self::MAX_CODE_BYTES) {
            throw new \InvalidArgumentException('Код не должен превышать 20 КБ.');
        }
        if (!str_contains($code, '<?php')) {
            $code = "<?php\n".$code;
        }

        $response = $this->httpClient->request('POST', rtrim($this->runnerUrl, '/').'/run', [
            'headers' => ['Authorization' => 'Bearer '.$this->runnerToken],
            'json' => ['code' => $code],
            'timeout' => 6,
        ]);
        $data = $response->toArray(false);
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException((string) ($data['error'] ?? 'Сервис песочницы временно недоступен.'));
        }

        return [
            'output' => (string) ($data['output'] ?? ''),
            'exit_code' => isset($data['exit_code']) ? (int) $data['exit_code'] : null,
            'timed_out' => (bool) ($data['timed_out'] ?? false),
        ];
    }
}
