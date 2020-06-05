<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200605123110 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-1613 Add arranger fields';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD target_arranger_commission NUMERIC(5, 4) DEFAULT NULL, ADD target_arranger_participation_money_amount NUMERIC(15, 2) NOT NULL, ADD target_arranger_participation_money_currency VARCHAR(3) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP target_arranger_commission, DROP target_arranger_participation_money_amount, DROP target_arranger_participation_money_currency');
    }
}
