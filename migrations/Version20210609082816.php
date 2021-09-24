<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210609082816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-3918 Add archving date field for members';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent_member ADD archiving_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE agency_borrower_member ADD archiving_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE agency_participation_member ADD archiving_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent_member DROP archiving_date');
        $this->addSql('ALTER TABLE agency_borrower_member DROP archiving_date');
        $this->addSql('ALTER TABLE agency_participation_member DROP archiving_date');
    }
}
