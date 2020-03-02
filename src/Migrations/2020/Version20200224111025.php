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
        $this->addSql("INSERT INTO translations (locale, section, name, translation, added, updated) VALUES ('fr_FR', 'staff-roles', 'duty_staff_auditor', 'Auditeur', '2020-02-24 12:32:29', null);");
        $this->addSql("UPDATE translations SET name = 'duty_staff_operator' WHERE id_translation = 298;");
        $this->addSql("UPDATE translations SET name = 'duty_staff_manager' WHERE id_translation = 299;");
        $this->addSql("UPDATE translations SET name = 'duty_staff_admin' WHERE id_translation = 300;");
        $this->addSql("UPDATE translations SET name = 'duty_staff_accountant' WHERE id_translation = 301;");
        $this->addSql("UPDATE translations SET name = 'duty_staff_signatory' WHERE id_translation = 302;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM translations WHERE section = 'staff-roles' AND name = 'duty_staff_auditor';");
        $this->addSql("UPDATE translations SET name = 'DUTY_STAFF_OPERATOR' WHERE id_translation = 298;");
        $this->addSql("UPDATE translations SET name = 'DUTY_STAFF_MANAGER' WHERE id_translation = 299;");
        $this->addSql("UPDATE translations SET name = 'DUTY_STAFF_ADMIN' WHERE id_translation = 300;");
        $this->addSql("UPDATE translations SET name = 'DUTY_STAFF_ACCOUNTANT' WHERE id_translation = 301;");
        $this->addSql("UPDATE translations SET name = 'DUTY_STAFF_SIGNATORY' WHERE id_translation = 302;");
    }
}
