<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210107150627 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-2785 Add bank details to agency project';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project ADD bank_institution VARCHAR(255) DEFAULT NULL, ADD bic VARCHAR(11) DEFAULT NULL, ADD iban VARCHAR(34) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project DROP bank_institution, DROP bic, DROP iban');
    }
}
