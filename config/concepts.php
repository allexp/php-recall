<?php

declare(strict_types=1);

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
        'material' => ['title' => 'Принципы ООП', 'slug' => 'oop-principles', 'definition' => 'Инкапсуляция, абстракция, наследование и полиморфизм.', 'short_description' => 'Основные средства моделирования поведения и взаимодействия объектов.', 'full_description' => '<p>Инкапсуляция защищает состояние, абстракция выделяет существенное, наследование переиспользует контракт, а полиморфизм позволяет работать с разными реализациями единообразно. На практике композиция часто гибче наследования.</p>', 'content_format' => 'html'],
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
