<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211001164001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4786 remove reporting_dates fields from credit_guaranty_reservation table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP reporting_first_date, DROP reporting_last_date, DROP reporting_validation_date');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD reporting_first_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD reporting_last_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD reporting_validation_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
