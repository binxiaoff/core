<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210519083654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3699 [CreditGuaranty] add id_managing_company in reservation table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD id_managing_company INT NOT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD CONSTRAINT FK_DE8BB8578E7654EC FOREIGN KEY (id_managing_company) REFERENCES core_company (id)');
        $this->addSql('CREATE INDEX IDX_DE8BB8578E7654EC ON credit_guaranty_reservation (id_managing_company)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP FOREIGN KEY FK_DE8BB8578E7654EC');
        $this->addSql('DROP INDEX IDX_DE8BB8578E7654EC ON credit_guaranty_reservation');
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP id_managing_company');
    }
}
