<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190507150623 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-128 Associate signature to attachment instead of project attachment';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE TABLE attachment_signature (id INT AUTO_INCREMENT NOT NULL, id_attachment INT NOT NULL, id_signatory INT NOT NULL, docusign_envelope_id INT DEFAULT NULL, status SMALLINT NOT NULL, updated DATETIME DEFAULT NULL, added DATETIME NOT NULL, INDEX IDX_D8505362DCD5596C (id_attachment), INDEX IDX_D85053622B0DC78F (id_signatory), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE attachment_signature ADD CONSTRAINT FK_D8505362DCD5596C FOREIGN KEY (id_attachment) REFERENCES attachment (id)');
        $this->addSql('ALTER TABLE attachment_signature ADD CONSTRAINT FK_D85053622B0DC78F FOREIGN KEY (id_signatory) REFERENCES clients (id_client)');
        $this->addSql('DROP TABLE project_attachment_signature');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE TABLE project_attachment_signature (id INT AUTO_INCREMENT NOT NULL, id_project_attachment INT NOT NULL, id_signatory INT NOT NULL, docusign_envelope_id INT DEFAULT NULL, status SMALLINT NOT NULL, updated DATETIME DEFAULT NULL, added DATETIME NOT NULL, INDEX IDX_FEB360CDE2DB1026 (id_project_attachment), INDEX IDX_FEB360CD2B0DC78F (id_signatory), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE project_attachment_signature ADD CONSTRAINT FK_FEB360CD2B0DC78F FOREIGN KEY (id_signatory) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE project_attachment_signature ADD CONSTRAINT FK_FEB360CDE2DB1026 FOREIGN KEY (id_project_attachment) REFERENCES project_attachment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE attachment_signature');
    }
}
