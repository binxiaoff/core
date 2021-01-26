<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210114102151 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2827 Add migration';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE agency_project ADD id_market_segment INT NOT NULL, ADD risk_group_name VARCHAR(255) NOT NULL, ADD internal_rating_score VARCHAR(8) DEFAULT NULL, ADD title VARCHAR(191) NOT NULL, ADD funding_specificity VARCHAR(10) DEFAULT NULL, ADD closing_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', ADD contract_end_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', ADD description MEDIUMTEXT DEFAULT NULL, ADD global_funding_money_amount NUMERIC(15, 2) NOT NULL, ADD global_funding_money_currency VARCHAR(3) NOT NULL');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BF2C71A0E3 FOREIGN KEY (id_market_segment) REFERENCES core_market_segment (id)');
        $this->addSql('CREATE INDEX IDX_59B349BF2C71A0E3 ON agency_project (id_market_segment)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BF2C71A0E3');
        $this->addSql('DROP INDEX IDX_59B349BF2C71A0E3 ON agency_project');
        $this->addSql('ALTER TABLE agency_project DROP id_market_segment, DROP risk_group_name, DROP internal_rating_score, DROP title, DROP funding_specificity, DROP closing_date, DROP contract_end_date, DROP description, DROP global_funding_money_amount, DROP global_funding_money_currency');

    }
}
