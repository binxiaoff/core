<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200224111025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1200 Add auditeur role';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added, updated) VALUES ('fr_FR', 'staff-roles', 'DUTY_STAFF_AUDITOR', 'Auditeur', '2020-02-24 12:32:29', null);");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM translations WHERE section = 'staff-roles' AND name = 'DUTY_STAFF_AUDITOR';");
    }
}
