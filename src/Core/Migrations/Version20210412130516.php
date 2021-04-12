<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210412130516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3084 Add personal drive to agency project participants (including agent)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_participation ADD id_personal_drive INT NOT NULL');
        $this->addSql('ALTER TABLE agency_participation ADD CONSTRAINT FK_E0ED689ED220E9F FOREIGN KEY (id_personal_drive) REFERENCES core_drive (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E0ED689ED220E9F ON agency_participation (id_personal_drive)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_participation DROP FOREIGN KEY FK_E0ED689ED220E9F');
        $this->addSql('DROP INDEX UNIQ_E0ED689ED220E9F ON agency_participation');
        $this->addSql('ALTER TABLE agency_participation DROP id_personal_drive');
    }
}
