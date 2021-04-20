<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210210200208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] Add missing legal form field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower ADD legal_form VARCHAR(100) NOT NULL');
        $this->addSql("UPDATE agency_borrower SET legal_form = 'SAS' WHERE TRUE");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower DROP legal_form');
    }
}
