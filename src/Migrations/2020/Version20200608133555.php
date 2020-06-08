<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200608133555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1613 Add new arranger field';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD target_arranger_participation_money_amount NUMERIC(15, 2) NOT NULL, ADD target_arranger_participation_money_currency VARCHAR(3) NOT NULL, ADD arrangement_commission_money_amount NUMERIC(15, 2) NOT NULL, ADD arrangement_commission_money_currency VARCHAR(3) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP target_arranger_participation_money_amount, DROP target_arranger_participation_money_currency, DROP arrangement_commission_money_amount, DROP arrangement_commission_money_currency');
    }
}
