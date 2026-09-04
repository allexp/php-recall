<?php

declare(strict_types=1);

namespace App\Service;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** Загружает, разбирает и кэширует страницы официального PHP Manual. */
final class ManualService
{
    /**
     * @param string $cacheDirectory Каталог для локального кэша разобранных страниц
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $cacheDirectory,
    ) {
    }

    /**
     * Возвращает подготовленную документацию функции, используя кэш при наличии.
     *
     * @param string $function Имя функции без круглых скобок
     *
     * @return array{summary: string, full: string, source: string}
     */
    public function get(string $function): array
    {
        $cacheFile = $this->cacheDirectory . '/' . $function . '.json';
        if (is_file($cacheFile)) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($cached) && isset($cached['summary'], $cached['full'], $cached['source'])) {
                return $cached;
            }
        }

        // В адресах PHP Manual символы подчёркивания в именах функций заменяются дефисами.
        $source = 'https://www.php.net/manual/ru/function.' . str_replace('_', '-', $function) . '.php';
        $response = $this->httpClient->request('GET', $source);
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('PHP Manual вернул ошибку при загрузке страницы.');
        }

        $result = $this->parse($response->getContent(), $source);
        if (!is_dir($this->cacheDirectory) && !mkdir($this->cacheDirectory, 0775, true) && !is_dir($this->cacheDirectory)) {
            throw new RuntimeException('Не удалось создать каталог кэша.');
        }
        file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);

        return $result;
    }

    /**
     * Извлекает назначение, сигнатуру и разделы описания из HTML-страницы.
     *
     * @return array{summary: string, full: string, source: string}
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
        $summary = '<p>' . htmlspecialchars($purpose, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
        $summary .= '<pre><code>' . htmlspecialchars($synopsis, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>';

        $full = '';
        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " refsect1 ")]') as $section) {
            if ($section instanceof DOMElement) {
                $full .= $document->saveHTML($section);
            }
        }
        if ($purpose === '' || $synopsis === '' || $full === '') {
            throw new RuntimeException('Формат страницы PHP Manual не удалось распознать.');
        }

        return ['summary' => $summary, 'full' => $this->sanitize($full), 'source' => $source];
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
