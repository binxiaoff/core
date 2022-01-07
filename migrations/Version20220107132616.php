<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220107132616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-5458 change some properties length from credit_guaranty_borrower and _project tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_borrower CHANGE beneficiary_name beneficiary_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_borrower CHANGE employees_number employees_number INT DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_project CHANGE detail detail LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_borrower CHANGE beneficiary_name beneficiary_name VARCHAR(40) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE credit_guaranty_borrower CHANGE employees_number employees_number SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE credit_guaranty_project CHANGE detail detail VARCHAR(1200) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
