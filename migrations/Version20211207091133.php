<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211207091133 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Syndication] CALS-5109 add variable capital';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent ADD variable_capital TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_borrower ADD variable_capital TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_participation ADD variable_capital TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent DROP variable_capital');
        $this->addSql('ALTER TABLE agency_borrower DROP variable_capital');
        $this->addSql('ALTER TABLE agency_participation DROP variable_capital');
    }
}
