<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200908103549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-2220 Add arrangement annual license';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE company_module ADD arrangement_annual_license_money_amount NUMERIC(15, 2) DEFAULT NULL, ADD arrangement_annual_license_money_currency VARCHAR(3) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE company_module DROP arrangement_annual_license_money_amount, DROP arrangement_annual_license_money_currency');
    }
}
