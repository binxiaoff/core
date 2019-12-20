<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191219103801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename id_project_offer to id_project_participation_offer on tranche_offer';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tranche_offer DROP FOREIGN KEY FK_4E7E9DEC84AB5FC5');
        $this->addSql('DROP INDEX IDX_4E7E9DEC84AB5FC5 ON tranche_offer');
        $this->addSql('DROP INDEX UNIQ_4E7E9DECB8FAF13084AB5FC5 ON tranche_offer');
        $this->addSql('ALTER TABLE tranche_offer CHANGE id_project_offer id_project_participation_offer INT NOT NULL');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC73C1DBA1 FOREIGN KEY (id_project_participation_offer) REFERENCES project_participation_offer (id)');
        $this->addSql('CREATE INDEX IDX_4E7E9DEC73C1DBA1 ON tranche_offer (id_project_participation_offer)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4E7E9DECB8FAF13073C1DBA1 ON tranche_offer (id_tranche, id_project_participation_offer)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE tranche_offer DROP FOREIGN KEY FK_4E7E9DEC73C1DBA1');
        $this->addSql('DROP INDEX IDX_4E7E9DEC73C1DBA1 ON tranche_offer');
        $this->addSql('DROP INDEX UNIQ_4E7E9DECB8FAF13073C1DBA1 ON tranche_offer');
        $this->addSql('ALTER TABLE tranche_offer CHANGE id_project_participation_offer id_project_offer INT NOT NULL');
        $this->addSql('ALTER TABLE tranche_offer ADD CONSTRAINT FK_4E7E9DEC84AB5FC5 FOREIGN KEY (id_project_offer) REFERENCES project_participation_offer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_4E7E9DEC84AB5FC5 ON tranche_offer (id_project_offer)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4E7E9DECB8FAF13084AB5FC5 ON tranche_offer (id_tranche, id_project_offer)');
    }
}
