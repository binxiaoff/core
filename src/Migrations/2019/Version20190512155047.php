<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190512155047 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-149 Add tranche id in loan and bid';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bids DROP FOREIGN KEY FK_3FF09E1EF12E799E');
        $this->addSql('DROP INDEX idprojectstatus ON bids');
        $this->addSql('ALTER TABLE bids CHANGE id_project id_tranche INT NOT NULL');
        $this->addSql('ALTER TABLE bids ADD CONSTRAINT FK_3FF09E1EB8FAF130 FOREIGN KEY (id_tranche) REFERENCES tranche (id)');
        $this->addSql('CREATE INDEX IDX_3FF09E1EB8FAF130 ON bids (id_tranche)');
        $this->addSql('CREATE INDEX IDX_3FF09E1EB8FAF1307B00651C ON bids (id_tranche, status)');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBCF12E799E');
        $this->addSql('DROP INDEX IDX_82C24DBCF12E799E ON loans');
        $this->addSql('DROP INDEX status ON loans');
        $this->addSql('ALTER TABLE loans CHANGE id_project id_tranche INT NOT NULL');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBCB8FAF130 FOREIGN KEY (id_tranche) REFERENCES tranche (id)');
        $this->addSql('CREATE INDEX IDX_82C24DBCB8FAF130 ON loans (id_tranche)');
        $this->addSql('CREATE INDEX IDX_82C24DBCB8FAF1307B00651C ON loans (id_tranche, status)');
        $this->addSql('ALTER TABLE loans RENAME INDEX idx_loans_added TO IDX_82C24DBCCBBF90EB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE bids DROP FOREIGN KEY FK_3FF09E1EB8FAF130');
        $this->addSql('DROP INDEX IDX_3FF09E1EB8FAF130 ON bids');
        $this->addSql('DROP INDEX IDX_3FF09E1EB8FAF1307B00651C ON bids');
        $this->addSql('ALTER TABLE bids CHANGE id_tranche id_project INT NOT NULL');
        $this->addSql('ALTER TABLE bids ADD CONSTRAINT FK_3FF09E1EF12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX idprojectstatus ON bids (id_project, status)');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBCB8FAF130');
        $this->addSql('DROP INDEX IDX_82C24DBCB8FAF130 ON loans');
        $this->addSql('DROP INDEX IDX_82C24DBCB8FAF1307B00651C ON loans');
        $this->addSql('ALTER TABLE loans CHANGE id_tranche id_project INT NOT NULL');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBCF12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_82C24DBCF12E799E ON loans (id_project)');
        $this->addSql('CREATE INDEX status ON loans (status)');
        $this->addSql('ALTER TABLE loans RENAME INDEX idx_82c24dbccbbf90eb TO idx_loans_added');
    }
}
