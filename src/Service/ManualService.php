<?php

declare(strict_types=1);

namespace App\Service;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** Загружает и разбирает страницы официального PHP Manual. */
final class ManualService
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    /** Загружает актуальную документацию функции из PHP Manual. */
    public function fetch(string $function): array
    {
        // В адресах PHP Manual символы подчёркивания в именах функций заменяются дефисами.
        $source = 'https://www.php.net/manual/ru/function.' . str_replace('_', '-', $function) . '.php';
        $response = $this->httpClient->request('GET', $source);
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('PHP Manual вернул ошибку при загрузке страницы.');
        }

        return $this->parse($response->getContent(), $source);
    }

    /**
     * Извлекает назначение, сигнатуру и разделы описания из HTML-страницы.
     *
     * @return array{definition: string, short_description: string, full_description: string, source_url: string}
     */
    private function parse(string $html, string $source): array
    {
        $document = new DOMDocument();
        // Документация может содержать разметку, не соответствующую строгому HTML,
        // поэтому диагностические сообщения libxml обрабатываются внутри сервиса.
        libxml_use_internal_errors(true);
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        $xpath = new DOMXPath($document);
        $purpose = trim((string) $xpath->evaluate('string(//*[contains(concat(" ", normalize-space(@class), " "), " refpurpose ")][1])'));
        $synopsisNode = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " methodsynopsis ")][1]')->item(0);
        $synopsis = preg_replace('/\s+/u', ' ', trim($synopsisNode?->textContent ?? '')) ?? '';
        $full = '';
        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " refsect1 ")]') as $section) {
            if ($section instanceof DOMElement) {
                $full .= $document->saveHTML($section);
            }
        }
        if ($purpose === '' || $synopsis === '' || $full === '') {
            throw new RuntimeException('Формат страницы PHP Manual не удалось распознать.');
        }

        return [
            'definition' => $synopsis,
            'short_description' => $purpose,
            'full_description' => $this->sanitize($full),
            'source_url' => $source,
        ];
    }

    /**
     * Оставляет безопасную разметку документации и нормализует внешние ссылки.
     */
    private function sanitize(string $html): string
    {
        $html = preg_replace('#<(script|style|form|button|nav|svg|img)[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = strip_tags($html, '<section><div><h2><h3><h4><p><pre><code><ul><ol><li><dl><dt><dd><table><thead><tbody><tr><th><td><strong><em><a><blockquote><span><var>');
        $html = preg_replace('/\s+(class|id|style|data-[\w-]+)=("[^"]*"|\'[^\']*\')/i', '', $html) ?? $html;

        return preg_replace_callback('/href=("|\')([^"\']+)\1/i', static function (array $match): string {
            $href = $match[2];
            if (str_starts_with($href, '/')) {
                $href = 'https://www.php.net' . $href;
            } elseif (!str_starts_with($href, '#') && !preg_match('#^https?://#i', $href)) {
                $href = 'https://www.php.net/manual/ru/' . ltrim($href, './');
            }

            return 'href="' . htmlspecialchars($href, ENT_QUOTES) . '" target="_blank" rel="noopener noreferrer"';
        }, $html) ?? $html;
    }
}
