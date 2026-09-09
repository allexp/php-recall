<?php

declare(strict_types=1);

namespace App\Service;

/** Предоставляет типизированный доступ к каталогу функций в базе данных. */
final class FunctionCatalog
{
    public function __construct(private readonly MaterialStore $materials)
    {
    }

    /**
     * Возвращает все категории каталога.
     *
     * @return array<string, array{title: string, functions: list<string>}>
     */
    public function all(): array
    {
        return $this->materials->functionCatalog();
    }

    /** Проверяет, разрешено ли запрашивать функцию через API. */
    public function contains(string $function): bool
    {
        return $this->materials->containsFunction($function);
    }
}
