<?php

declare(strict_types=1);

namespace App\Service;

/** Предоставляет каталог концепций разработки. */
final class ConceptCatalog
{
    public function __construct(private readonly MaterialStore $materials)
    {
    }

    /** Возвращает сгруппированный список концепций. */
    public function all(): array
    {
        return $this->materials->conceptCatalog();
    }

    /** Возвращает концепцию со всеми подсекциями и примерами. */
    public function get(string $slug): ?array
    {
        return $this->materials->material('concept', $slug);
    }
}
