<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210128100003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add team edge table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE core_team_edge (id INT AUTO_INCREMENT NOT NULL, id_ancestor INT DEFAULT NULL, id_descendent INT DEFAULT NULL, depth INT NOT NULL, INDEX IDX_EB8717395B9F892E (id_ancestor), INDEX IDX_EB871739380C851D (id_descendent), UNIQUE INDEX uniq_team_edge_ancestor_descendent (id_ancestor, id_descendent), UNIQUE INDEX uniq_team_edge_descendent_depth (id_descendent, depth), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE core_team_edge ADD CONSTRAINT FK_EB8717395B9F892E FOREIGN KEY (id_ancestor) REFERENCES core_team (id)');
        $this->addSql('ALTER TABLE core_team_edge ADD CONSTRAINT FK_EB871739380C851D FOREIGN KEY (id_descendent) REFERENCES core_team (id)');
        $this->addSql('ALTER TABLE core_team DROP FOREIGN KEY FK_C4E0A61F1BB9D5A2');
        $this->addSql('DROP INDEX IDX_F605652A1BB9D5A2 ON core_team');
        $this->addSql('ALTER TABLE core_team DROP id_parent');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE core_team_edge');
        $this->addSql('ALTER TABLE core_team ADD id_parent INT DEFAULT NULL');
        $this->addSql('ALTER TABLE core_team ADD CONSTRAINT FK_C4E0A61F1BB9D5A2 FOREIGN KEY (id_parent) REFERENCES core_team (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_F605652A1BB9D5A2 ON core_team (id_parent)');
    }
}
