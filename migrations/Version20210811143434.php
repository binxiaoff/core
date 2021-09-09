<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210811143434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Arrangement] CALS-4362 Add missing on delete cascade for ProjectParticipationTranche::tranche';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE syndication_project_participation_tranche DROP FOREIGN KEY FK_48EF5E16B8FAF130');
        $this->addSql('ALTER TABLE syndication_project_participation_tranche ADD CONSTRAINT FK_48EF5E16B8FAF130 FOREIGN KEY (id_tranche) REFERENCES syndication_tranche (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE syndication_project_participation_tranche DROP FOREIGN KEY FK_48EF5E16B8FAF130');
        $this->addSql('ALTER TABLE syndication_project_participation_tranche ADD CONSTRAINT FK_48EF5E16B8FAF130 FOREIGN KEY (id_tranche) REFERENCES syndication_tranche (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
