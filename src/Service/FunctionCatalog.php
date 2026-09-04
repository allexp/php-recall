<?php

declare(strict_types=1);

namespace App\Service;

/** Предоставляет типизированный доступ к настроенному каталогу функций. */
final class FunctionCatalog
{
    /** @var array<string, array{title: string, functions: list<string>}> */
    private readonly array $categories;

    /** Загружает каталог из PHP-файла конфигурации. */
    public function __construct(string $catalogFile)
    {
        $this->categories = require $catalogFile;
    }

    /**
     * Возвращает все категории каталога.
     *
     * @return array<string, array{title: string, functions: list<string>}>
     */
    public function all(): array
    {
        return $this->categories;
    }

    /** Проверяет, разрешено ли запрашивать функцию через API. */
    public function contains(string $function): bool
    {
        foreach ($this->categories as $category) {
            if (in_array($function, $category['functions'], true)) {
                return true;
            }
        }

        return false;
    }
}
