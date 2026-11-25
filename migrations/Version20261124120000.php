<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Уточняет русские названия и описания принципов SOLID. */
final class Version20261124120000 extends AbstractMigration
{
    private const MATERIAL_ID = 231;

    private const DESCRIPTIONS = [
        'srp' => '<p><strong>Принцип единственной ответственности.</strong> У класса или модуля должна быть одна причина для изменения, связанная с одним актором или одной областью ответственности. Бизнес-логику следует отделять от сохранения, форматирования и доставки данных.</p>',
        'ocp' => '<p><strong>Принцип открытости/закрытости.</strong> Программные сущности должны быть открыты для расширения, но закрыты для изменения: новое поведение добавляют через композицию, полиморфизм или другие точки расширения, не изменяя уже проверенный код.</p>',
        'lsp' => '<p><strong>Принцип подстановки Лисков.</strong> Объект подтипа должен использоваться вместо объекта базового типа без нарушения корректности программы. Подтип не должен усиливать предусловия, ослаблять постусловия или нарушать инварианты базового типа.</p>',
        'isp' => '<p><strong>Принцип разделения интерфейса.</strong> Клиенты не должны зависеть от методов, которые они не используют. Несколько небольших специализированных интерфейсов обычно предпочтительнее одного универсального.</p>',
        'dip' => '<p><strong>Принцип инверсии зависимостей.</strong> Высокоуровневая политика не должна зависеть от низкоуровневых деталей: и та и другая должны зависеть от абстракций. Абстракции не зависят от деталей, а детали зависят от абстракций.</p>',
    ];

    private const PREVIOUS_DESCRIPTIONS = [
        'srp' => '<p>У модуля должна быть одна причина для изменения. Отделяйте бизнес-логику от сохранения, форматирования и доставки данных.</p>',
        'ocp' => '<p>Сущности открыты для расширения, но закрыты для изменения. Новое поведение добавляется через композицию или полиморфизм.</p>',
        'lsp' => '<p>Подтип должен заменять базовый тип без нарушения ожидаемого поведения и контрактов вызывающего кода.</p>',
        'isp' => '<p>Клиенты не должны зависеть от методов, которые им не нужны. Несколько узких интерфейсов обычно лучше одного универсального.</p>',
        'dip' => '<p>Высокоуровневая логика зависит от абстракций, а детали подключаются снаружи. Это упрощает замену реализаций и тестирование.</p>',
    ];

    public function getDescription(): string
    {
        return 'Добавляет русские названия и уточняет описания пяти принципов SOLID';
    }

    public function up(Schema $schema): void
    {
        $this->updateDescriptions(self::DESCRIPTIONS);
    }

    public function down(Schema $schema): void
    {
        $this->updateDescriptions(self::PREVIOUS_DESCRIPTIONS);
    }

    /** @param array<string, string> $descriptions */
    private function updateDescriptions(array $descriptions): void
    {
        foreach ($descriptions as $slug => $description) {
            $this->addSql(
                'UPDATE material_sections
                 SET description = :description
                 WHERE material_id = :material_id
                   AND slug = :slug',
                [
                    'material_id' => self::MATERIAL_ID,
                    'slug' => $slug,
                    'description' => $description,
                ],
            );
        }
    }
}
