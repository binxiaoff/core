<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210128102233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3113 Add agency contact nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project ADD agency_contact_first_name VARCHAR(255) DEFAULT NULL, ADD agency_contact_last_name VARCHAR(255) DEFAULT NULL, ADD agency_contact_parent_unit VARCHAR(255) DEFAULT NULL, ADD agency_contact_occupation VARCHAR(255) DEFAULT NULL, ADD agency_contact_email VARCHAR(255) DEFAULT NULL, ADD agency_contact_phone VARCHAR(35) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project DROP agency_contact_first_name, DROP agency_contact_last_name, DROP agency_contact_parent_unit, DROP agency_contact_occupation, DROP agency_contact_email, DROP agency_contact_phone');
    }
}
