<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191107131059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-492';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD global_funding_money_amount NUMERIC(15, 2) NOT NULL, ADD global_funding_money_currency VARCHAR(3) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP global_funding_money_amount, DROP global_funding_money_currency');
    }
}
