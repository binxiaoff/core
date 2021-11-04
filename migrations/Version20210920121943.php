<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210920121943 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4449 add new properties in credit_guaranty_program, credit_guaranty_financing_object and credit_guaranty_reservation tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program ADD rating_model VARCHAR(16) NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object ADD reporting_first_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD reporting_last_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD reporting_validation_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD reporting_first_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD reporting_last_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD reporting_validation_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program DROP rating_model');
        $this->addSql('ALTER TABLE credit_guaranty_financing_object DROP reporting_first_date, DROP reporting_last_date, DROP reporting_validation_date');
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP reporting_first_date, DROP reporting_last_date, DROP reporting_validation_date');
    }
}
