<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210412131907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3086 Add borrower drive';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project ADD id_borrower_drive INT NOT NULL');
        $this->addSql('ALTER TABLE agency_project ADD CONSTRAINT FK_59B349BFC2E752FB FOREIGN KEY (id_borrower_drive) REFERENCES core_drive (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_59B349BFC2E752FB ON agency_project (id_borrower_drive)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_project DROP FOREIGN KEY FK_59B349BFC2E752FB');
        $this->addSql('DROP INDEX UNIQ_59B349BFC2E752FB ON agency_project');
        $this->addSql('ALTER TABLE agency_project DROP id_borrower_drive');
    }
}
