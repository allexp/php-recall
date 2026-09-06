<?php

declare(strict_types=1);

namespace App\Service;

/** Предоставляет типизированный доступ к настроенному каталогу функций. */
final class FunctionCatalog
{
    /** Синхронизирует конфигурацию каталога с базой материалов. */
    public function __construct(
        private readonly MaterialStore $materials,
        string $catalogFile,
    ) {
        /** @var array<string, array{title: string, functions: list<string>}> $catalog */
        $catalog = require $catalogFile;
        $this->materials->synchroniseFunctionCatalog($catalog);
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
