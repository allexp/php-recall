<?php

declare(strict_types=1);

namespace App\Service;

/** Предоставляет учебные карточки по фреймворкам. */
final class FrameworkCatalog
{
    public function __construct(private readonly MaterialStore $materials)
    {
    }

    /** Возвращает сгруппированный список карточек. */
    public function all(): array
    {
        return $this->materials->frameworkCatalog();
    }

    /** Возвращает карточку с полным объяснением. */
    public function get(string $slug): ?array
    {
        return $this->materials->material('framework', $slug);
    }
}
