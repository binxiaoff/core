<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20200128110103 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    private const TRANSLATIONS = [
        'DUTY_STAFF_OPERATOR'   => 'Opérateur',
        'DUTY_STAFF_MANAGER'    => 'Manager',
        'DUTY_STAFF_ADMIN'      => 'Administrateur',
        'DUTY_STAFF_ACCOUNTANT' => 'Comptable',
        'DUTY_STAFF_SIGNATORY'  => 'Signataire',
    ];

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Add staff roles translations';
    }

    public function up(Schema $schema): void
    {
        foreach (static::TRANSLATIONS as $name => $translation) {
            $this->addSql("INSERT INTO translations(locale, section, name, translation, added, updated) VALUES ('fr_FR', 'staff-roles', '{$name}', '{$translation}', NOW(), NULL)");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM translations where section = 'staff-roles'");
    }
}
