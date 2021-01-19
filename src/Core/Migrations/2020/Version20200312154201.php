<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200312154201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1187 PSN signature';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE attachment_signature DROP FOREIGN KEY FK_D85053622B0DC78F');
        $this->addSql('ALTER TABLE attachment_signature ADD added_by INT NOT NULL, ADD public_id VARCHAR(36) NOT NULL, DROP docusign_envelope_id');
        $this->addSql('ALTER TABLE attachment_signature ADD CONSTRAINT FK_D8505362699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE attachment_signature ADD CONSTRAINT FK_D85053622B0DC78F FOREIGN KEY (id_signatory) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE attachment_signature ADD transaction_number VARCHAR(100) DEFAULT NULL, ADD signature_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D8505362B5B48B91 ON attachment_signature (public_id)');
        $this->addSql('CREATE INDEX IDX_D8505362699B6BAF ON attachment_signature (added_by)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE attachment_signature DROP FOREIGN KEY FK_D8505362699B6BAF');
        $this->addSql('ALTER TABLE attachment_signature DROP FOREIGN KEY FK_D85053622B0DC78F');
        $this->addSql('DROP INDEX UNIQ_D8505362B5B48B91 ON attachment_signature');
        $this->addSql('DROP INDEX IDX_D8505362699B6BAF ON attachment_signature');
        $this->addSql('ALTER TABLE attachment_signature DROP transaction_number, DROP signature_url');
        $this->addSql('ALTER TABLE attachment_signature ADD docusign_envelope_id INT DEFAULT NULL, DROP added_by, DROP public_id');
        $this->addSql('ALTER TABLE attachment_signature ADD CONSTRAINT FK_D85053622B0DC78F FOREIGN KEY (id_signatory) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
