<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210726162800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Syndication] CALS-2304 rename id_description_document into id_term_sheet';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE syndication_project DROP FOREIGN KEY FK_7E9E0E6F61AA99F6');
        $this->addSql('DROP INDEX UNIQ_7E9E0E6F61AA99F6 ON syndication_project');
        $this->addSql('ALTER TABLE syndication_project CHANGE id_description_document id_term_sheet INT DEFAULT NULL');
        $this->addSql('ALTER TABLE syndication_project ADD CONSTRAINT FK_7E9E0E6F17846EBD FOREIGN KEY (id_term_sheet) REFERENCES core_file (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7E9E0E6F17846EBD ON syndication_project (id_term_sheet)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE syndication_project DROP FOREIGN KEY FK_7E9E0E6F17846EBD');
        $this->addSql('DROP INDEX UNIQ_7E9E0E6F17846EBD ON syndication_project');
        $this->addSql('ALTER TABLE syndication_project CHANGE id_term_sheet id_description_document INT DEFAULT NULL');
        $this->addSql('ALTER TABLE syndication_project ADD CONSTRAINT FK_7E9E0E6F61AA99F6 FOREIGN KEY (id_description_document) REFERENCES core_file (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7E9E0E6F61AA99F6 ON syndication_project (id_description_document)');
    }
}
