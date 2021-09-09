<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210215164544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] Make validityDate nullable in Tranche';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_tranche CHANGE validity_date validity_date date NULL COMMENT \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_tranche CHANGE validity_date validity_date date NOT NULL COMMENT \'(DC2Type:date_immutable)\'');
    }
}
