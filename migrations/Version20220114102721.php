<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220114102721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-4634 add company to reporting';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_reporting ADD id_company INT DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_reporting ADD CONSTRAINT FK_95727FFB9122A03F FOREIGN KEY (id_company) REFERENCES core_company (id)');
        $this->addSql('CREATE INDEX IDX_95727FFB9122A03F ON credit_guaranty_reporting (id_company)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_reporting DROP FOREIGN KEY FK_95727FFB9122A03F');
        $this->addSql('DROP INDEX IDX_95727FFB9122A03F ON credit_guaranty_reporting');
        $this->addSql('ALTER TABLE credit_guaranty_reporting DROP id_company');
    }
}
