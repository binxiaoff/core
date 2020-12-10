<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200724134450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1997 SyndicationType & participantType nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project MODIFY syndication_type VARCHAR(80) DEFAULT NULL, MODIFY participation_type VARCHAR(80) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project MODIFY syndication_type VARCHAR(80) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, MODIFY participation_type VARCHAR(80) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
