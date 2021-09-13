<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210114102151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-2827 Add field to agency project';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project ADD risk_group_name VARCHAR(255) NOT NULL, ADD internal_rating_score VARCHAR(8) DEFAULT NULL, ADD title VARCHAR(191) NOT NULL, ADD funding_specificity VARCHAR(10) DEFAULT NULL, ADD closing_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', ADD contract_end_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', ADD description MEDIUMTEXT DEFAULT NULL, ADD global_funding_money_amount NUMERIC(15, 2) NOT NULL, ADD global_funding_money_currency VARCHAR(3) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project DROP risk_group_name, DROP internal_rating_score, DROP title, DROP funding_specificity, DROP closing_date, DROP contract_end_date, DROP description, DROP global_funding_money_amount, DROP global_funding_money_currency');
    }
}
