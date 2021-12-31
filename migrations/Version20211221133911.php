<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211221133911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] Remove unneeded unique constraint on archiver';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent_member DROP FOREIGN KEY FK_925D3B7BA430C03C');
        $this->addSql('DROP INDEX UNIQ_925D3B7BA430C03C ON agency_agent_member');
        $this->addSql('ALTER TABLE agency_agent_member CHANGE archiver_id id_archiver INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_agent_member ADD CONSTRAINT FK_925D3B7BC5BE303A FOREIGN KEY (id_archiver) REFERENCES core_user (id)');
        $this->addSql('CREATE INDEX IDX_925D3B7BC5BE303A ON agency_agent_member (id_archiver)');
        $this->addSql('ALTER TABLE agency_borrower_member DROP FOREIGN KEY FK_5B36A3AAA430C03C');
        $this->addSql('DROP INDEX UNIQ_5B36A3AAA430C03C ON agency_borrower_member');
        $this->addSql('ALTER TABLE agency_borrower_member CHANGE archiver_id id_archiver INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_borrower_member ADD CONSTRAINT FK_5B36A3AAC5BE303A FOREIGN KEY (id_archiver) REFERENCES core_user (id)');
        $this->addSql('CREATE INDEX IDX_5B36A3AAC5BE303A ON agency_borrower_member (id_archiver)');
        $this->addSql('ALTER TABLE agency_participation_member DROP FOREIGN KEY FK_D4BCDFFBA430C03C');
        $this->addSql('DROP INDEX UNIQ_D4BCDFFBA430C03C ON agency_participation_member');
        $this->addSql('ALTER TABLE agency_participation_member CHANGE archiver_id id_archiver INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_participation_member ADD CONSTRAINT FK_D4BCDFFBC5BE303A FOREIGN KEY (id_archiver) REFERENCES core_user (id)');
        $this->addSql('CREATE INDEX IDX_D4BCDFFBC5BE303A ON agency_participation_member (id_archiver)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent_member DROP FOREIGN KEY FK_925D3B7BC5BE303A');
        $this->addSql('DROP INDEX IDX_925D3B7BC5BE303A ON agency_agent_member');
        $this->addSql('ALTER TABLE agency_agent_member CHANGE id_archiver archiver_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_agent_member ADD CONSTRAINT FK_925D3B7BA430C03C FOREIGN KEY (archiver_id) REFERENCES core_user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_925D3B7BA430C03C ON agency_agent_member (archiver_id)');
        $this->addSql('ALTER TABLE agency_borrower_member DROP FOREIGN KEY FK_5B36A3AAC5BE303A');
        $this->addSql('DROP INDEX IDX_5B36A3AAC5BE303A ON agency_borrower_member');
        $this->addSql('ALTER TABLE agency_borrower_member CHANGE id_archiver archiver_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_borrower_member ADD CONSTRAINT FK_5B36A3AAA430C03C FOREIGN KEY (archiver_id) REFERENCES core_user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5B36A3AAA430C03C ON agency_borrower_member (archiver_id)');
        $this->addSql('ALTER TABLE agency_participation_member DROP FOREIGN KEY FK_D4BCDFFBC5BE303A');
        $this->addSql('DROP INDEX IDX_D4BCDFFBC5BE303A ON agency_participation_member');
        $this->addSql('ALTER TABLE agency_participation_member CHANGE id_archiver archiver_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_participation_member ADD CONSTRAINT FK_D4BCDFFBA430C03C FOREIGN KEY (archiver_id) REFERENCES core_user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D4BCDFFBA430C03C ON agency_participation_member (archiver_id)');
    }
}
