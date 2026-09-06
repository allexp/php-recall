<?php

declare(strict_types=1);

namespace App\Service;

/** Наполняет и предоставляет каталог концепций разработки. */
final class ConceptCatalog
{
    public function __construct(
        private readonly MaterialStore $materials,
        string $catalogFile,
    ) {
        /** @var list<array<string, mixed>> $catalog */
        $catalog = require $catalogFile;
        foreach ($catalog as $position => $item) {
            $material = $item['material'];
            $material['position'] = $position;
            $materialId = $this->materials->saveMaterial(
                'concept',
                'Концепции разработки',
                $item['category']['code'],
                $item['category']['title'],
                $material,
            );
            $this->materials->replaceCodeExamples($materialId, $item['examples'] ?? []);
            $this->materials->replaceSections($materialId, $item['sections'] ?? []);
        }

        // Удаляем прежнюю объединённую карточку после разделения принципов ООП.
        $this->materials->deleteMaterial('concept', 'oop-principles');
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
