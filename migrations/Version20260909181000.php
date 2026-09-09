<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Переносит каталог концепций разработки в базу учебных материалов. */
final class Version20260909181000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Добавляет учебные материалы по концепциям разработки';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO material_types (code, title) VALUES ('concept', 'Концепции разработки') ON CONFLICT(code) DO UPDATE SET title = excluded.title");

        $categoryPositions = [];
        foreach ($this->concepts() as $position => $item) {
            $category = $item['category'];
            $categoryPositions[$category['code']] ??= count($categoryPositions);
            $this->addSql(
                "INSERT INTO categories (type_id, code, title, position) VALUES ((SELECT id FROM material_types WHERE code = 'concept'), :code, :title, :position) ON CONFLICT(type_id, code) DO UPDATE SET title = excluded.title, position = excluded.position",
                [
                    'code' => $category['code'],
                    'title' => $category['title'],
                    'position' => $categoryPositions[$category['code']],
                ],
            );

            $material = $item['material'];
            $this->addSql(
                "INSERT INTO materials (type_id, category_id, title, slug, definition, short_description, full_description, content_format, source_url, position, created_at, updated_at)
                 VALUES ((SELECT id FROM material_types WHERE code = 'concept'), (SELECT categories.id FROM categories JOIN material_types ON material_types.id = categories.type_id WHERE material_types.code = 'concept' AND categories.code = :category), :title, :slug, :definition, :short_description, :full_description, :content_format, :source_url, :position, :created_at, :updated_at)
                 ON CONFLICT(type_id, slug) DO UPDATE SET category_id = excluded.category_id, title = excluded.title, definition = excluded.definition, short_description = excluded.short_description, full_description = excluded.full_description, content_format = excluded.content_format, source_url = excluded.source_url, position = excluded.position, updated_at = excluded.updated_at",
                [
                    'category' => $category['code'],
                    'title' => $material['title'],
                    'slug' => $material['slug'],
                    'definition' => $material['definition'],
                    'short_description' => $material['short_description'],
                    'full_description' => $material['full_description'],
                    'content_format' => $material['content_format'] ?? 'html',
                    'source_url' => $material['source_url'] ?? null,
                    'position' => $position,
                    'created_at' => '2026-09-09T18:10:00+03:00',
                    'updated_at' => '2026-09-09T18:10:00+03:00',
                ],
            );

            $materialId = "(SELECT materials.id FROM materials JOIN material_types ON material_types.id = materials.type_id WHERE material_types.code = 'concept' AND materials.slug = :slug)";
            $this->addSql("DELETE FROM code_examples WHERE material_id = $materialId", ['slug' => $material['slug']]);
            $this->addSql("DELETE FROM material_sections WHERE material_id = $materialId", ['slug' => $material['slug']]);

            foreach ($item['examples'] ?? [] as $examplePosition => $example) {
                $this->addSql(
                    "INSERT INTO code_examples (material_id, title, language, code, position) VALUES ($materialId, :title, :language, :code, :position)",
                    [
                        'slug' => $material['slug'],
                        'title' => $example['title'] ?? '',
                        'language' => $example['language'],
                        'code' => $example['code'],
                        'position' => $examplePosition,
                    ],
                );
            }

            foreach ($item['sections'] ?? [] as $sectionPosition => $section) {
                $this->addSql(
                    "INSERT INTO material_sections (material_id, title, slug, description, position) VALUES ($materialId, :title, :section_slug, :description, :position)",
                    [
                        'slug' => $material['slug'],
                        'title' => $section['title'],
                        'section_slug' => $section['slug'],
                        'description' => $section['description'],
                        'position' => $sectionPosition,
                    ],
                );
                foreach ($section['examples'] ?? [] as $examplePosition => $example) {
                    $this->addSql(
                        "INSERT INTO code_examples (material_id, section_id, title, language, code, position)
                         VALUES ($materialId, (SELECT material_sections.id FROM material_sections WHERE material_id = $materialId AND material_sections.slug = :section_slug), :title, :language, :code, :position)",
                        [
                            'slug' => $material['slug'],
                            'section_slug' => $section['slug'],
                            'title' => $example['title'] ?? '',
                            'language' => $example['language'],
                            'code' => $example['code'],
                            'position' => $examplePosition,
                        ],
                    );
                }
            }
        }

        $this->addSql("DELETE FROM materials WHERE type_id = (SELECT id FROM material_types WHERE code = 'concept') AND slug = 'oop-principles'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM materials WHERE type_id = (SELECT id FROM material_types WHERE code = 'concept')");
        $this->addSql("DELETE FROM categories WHERE type_id = (SELECT id FROM material_types WHERE code = 'concept')");
        $this->addSql("DELETE FROM material_types WHERE code = 'concept'");
    }

    /** Возвращает материалы по концепциям разработки. */
    private function concepts(): array
    {
        $solidExample = <<<'PHP'
interface ReportExporter
{
    public function export(Report $report): string;
}

final class PdfExporter implements ReportExporter
{
    public function export(Report $report): string
    {
        return 'PDF: '.$report->title;
    }
}
PHP;

        return [
    [
        'category' => ['code' => 'principles', 'title' => 'Принципы проектирования'],
        'material' => [
            'title' => 'SOLID',
            'slug' => 'solid',
            'definition' => 'Пять принципов объектно-ориентированного проектирования.',
            'short_description' => 'SOLID помогает разделять ответственность, уменьшать связанность и безопаснее развивать код.',
            'full_description' => '<p>SOLID — мнемоника для пяти принципов проектирования классов и модулей. Это не строгие законы, а ориентиры: их применяют там, где они делают изменения понятнее и дешевле.</p>',
            'content_format' => 'html',
            'source_url' => 'https://en.wikipedia.org/wiki/SOLID',
        ],
        'examples' => [
            ['title' => 'Расширяемый экспорт отчётов', 'language' => 'php', 'code' => $solidExample],
        ],
        'sections' => [
            ['title' => 'S — Single Responsibility', 'slug' => 'srp', 'description' => '<p>У модуля должна быть одна причина для изменения. Отделяйте бизнес-логику от сохранения, форматирования и доставки данных.</p>', 'examples' => [['title' => 'Разделение обязанностей', 'language' => 'php', 'code' => "final class InvoiceCalculator {}\nfinal class InvoiceRepository {}\nfinal class InvoiceMailer {}"]]],
            ['title' => 'O — Open/Closed', 'slug' => 'ocp', 'description' => '<p>Сущности открыты для расширения, но закрыты для изменения. Новое поведение добавляется через композицию или полиморфизм.</p>', 'examples' => [['title' => 'Расширение через интерфейс', 'language' => 'php', 'code' => $solidExample]]],
            ['title' => 'L — Liskov Substitution', 'slug' => 'lsp', 'description' => '<p>Подтип должен заменять базовый тип без нарушения ожидаемого поведения и контрактов вызывающего кода.</p>'],
            ['title' => 'I — Interface Segregation', 'slug' => 'isp', 'description' => '<p>Клиенты не должны зависеть от методов, которые им не нужны. Несколько узких интерфейсов обычно лучше одного универсального.</p>'],
            ['title' => 'D — Dependency Inversion', 'slug' => 'dip', 'description' => '<p>Высокоуровневая логика зависит от абстракций, а детали подключаются снаружи. Это упрощает замену реализаций и тестирование.</p>'],
        ],
    ],
    [
        'category' => ['code' => 'principles', 'title' => 'Принципы проектирования'],
        'material' => ['title' => 'DRY', 'slug' => 'dry', 'definition' => 'Don’t Repeat Yourself — не дублируйте знание.', 'short_description' => 'Каждое значимое правило системы должно иметь одно авторитетное представление.', 'full_description' => '<p>DRY относится прежде всего к дублированию знания, а не к любым похожим строкам кода. Преждевременное объединение случайно похожего поведения создаёт неверные абстракции.</p>', 'content_format' => 'html'],
    ],
    [
        'category' => ['code' => 'principles', 'title' => 'Принципы проектирования'],
        'material' => ['title' => 'KISS', 'slug' => 'kiss', 'definition' => 'Keep It Simple — сохраняйте решение простым.', 'short_description' => 'Выбирайте самое простое решение, которое явно выполняет текущие требования.', 'full_description' => '<p>Простота означает малое число движущихся частей и очевидные связи. Она не оправдывает игнорирование требований, но помогает не оплачивать сложность заранее.</p>', 'content_format' => 'html'],
    ],
    [
        'category' => ['code' => 'oop', 'title' => 'Объектно-ориентированное программирование'],
        'material' => [
            'title' => 'Инкапсуляция',
            'slug' => 'encapsulation',
            'definition' => 'Объединение данных и работающего с ними поведения с контролем доступа к внутреннему состоянию объекта.',
            'short_description' => 'Объект сам поддерживает свои инварианты и предоставляет наружу только необходимый интерфейс.',
            'full_description' => '<p>Инкапсуляция скрывает детали хранения и изменения состояния за публичным контрактом объекта. Вместо прямого изменения полей вызывающий код использует методы, которые проверяют допустимость операции и сохраняют инварианты.</p><p>Модификаторы доступа помогают реализовать инкапсуляцию, но не исчерпывают её: главное — не раскрывать решения, от которых клиентскому коду не следует зависеть. Благодаря этому внутреннее представление можно менять без изменения пользователей класса.</p>',
            'content_format' => 'html',
        ],
    ],
    [
        'category' => ['code' => 'oop', 'title' => 'Объектно-ориентированное программирование'],
        'material' => [
            'title' => 'Абстракция',
            'slug' => 'abstraction',
            'definition' => 'Выделение существенных характеристик объекта и сокрытие деталей, не важных для текущего уровня рассмотрения.',
            'short_description' => 'Абстракция описывает, что умеет компонент, не заставляя клиента знать, как это реализовано.',
            'full_description' => '<p>Абстракция позволяет представить сложную систему через небольшой и понятный набор понятий и операций. Интерфейс, абстрактный класс или простой публичный API задаёт контракт, а технические подробности остаются внутри реализации.</p><p>Хорошая абстракция отражает устойчивую идею предметной области. Она уменьшает связанность: клиент зависит от возможностей компонента, а не от конкретного алгоритма, хранилища или внешнего сервиса.</p>',
            'content_format' => 'html',
        ],
    ],
    [
        'category' => ['code' => 'oop', 'title' => 'Объектно-ориентированное программирование'],
        'material' => [
            'title' => 'Наследование',
            'slug' => 'inheritance',
            'definition' => 'Механизм создания нового класса на основе существующего с получением его контракта и доступного поведения.',
            'short_description' => 'Наследование выражает отношение «является» и позволяет специализировать поведение базового типа.',
            'full_description' => '<p>Дочерний класс наследует доступные свойства и методы родительского класса и может добавлять или переопределять поведение. Это позволяет использовать экземпляр потомка там, где ожидается базовый тип, если потомок соблюдает его контракт.</p><p>Наследование создаёт сильную связь с базовым классом, поэтому его применяют для настоящей специализации, а не только ради повторного использования кода. Когда отношение «является» не выполняется, композиция обычно даёт более гибкую конструкцию.</p>',
            'content_format' => 'html',
        ],
    ],
    [
        'category' => ['code' => 'oop', 'title' => 'Объектно-ориентированное программирование'],
        'material' => [
            'title' => 'Полиморфизм',
            'slug' => 'polymorphism',
            'definition' => 'Возможность единообразно работать с объектами разных типов через общий контракт.',
            'short_description' => 'Один вызов может приводить к разному поведению в зависимости от конкретной реализации объекта.',
            'full_description' => '<p>Полиморфизм позволяет клиентскому коду обращаться к интерфейсу или базовому типу, не проверяя конкретный класс объекта. Каждая реализация самостоятельно выполняет операцию согласно общему контракту.</p><p>Например, сервис оплаты может принимать интерфейс платёжного шлюза, а во время выполнения работать с банковской картой, СБП или тестовой реализацией. Новые варианты добавляются без цепочек условных операторов в клиентском коде.</p>',
            'content_format' => 'html',
        ],
    ],
    [
        'category' => ['code' => 'patterns', 'title' => 'Шаблоны проектирования'],
        'material' => ['title' => 'Шаблоны проектирования', 'slug' => 'design-patterns', 'definition' => 'Повторяемые схемы решения типичных задач проектирования.', 'short_description' => 'Общий словарь для обсуждения создания объектов, структуры и взаимодействия компонентов.', 'full_description' => '<p>Порождающие шаблоны управляют созданием объектов, структурные — их компоновкой, поведенческие — взаимодействием. Шаблон стоит применять только при наличии соответствующей задачи.</p>', 'content_format' => 'html'],
        'sections' => [
            ['title' => 'Порождающие', 'slug' => 'creational', 'description' => '<p>Factory Method, Abstract Factory, Builder, Prototype и Singleton.</p>'],
            ['title' => 'Структурные', 'slug' => 'structural', 'description' => '<p>Adapter, Bridge, Composite, Decorator, Facade, Flyweight и Proxy.</p>'],
            ['title' => 'Поведенческие', 'slug' => 'behavioral', 'description' => '<p>Strategy, Observer, Command, State, Template Method и другие шаблоны взаимодействия.</p>'],
        ],
    ],
        ];
    }
}
