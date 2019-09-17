<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190913094525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-332';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE clients_status_history DROP FOREIGN KEY FK_3E28AF075D37D0F1');
        $this->addSql('DROP TABLE clients_status');
        $this->addSql('ALTER TABLE clients DROP FOREIGN KEY FK_C82E74DF1ED241');
        $this->addSql('DROP INDEX IDX_C82E74DF1ED241 ON clients');
        $this->addSql('ALTER TABLE clients RENAME COLUMN id_client_status_history TO id_current_status');
        $this->addSql('ALTER TABLE clients ADD CONSTRAINT FK_C82E74B0D1B111 FOREIGN KEY (id_current_status) REFERENCES clients_status_history (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C82E74B0D1B111 ON clients (id_current_status)');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEC60C84FB');
        $this->addSql('DROP INDEX UNIQ_2FB3D0EEC60C84FB ON project');
        $this->addSql('ALTER TABLE project CHANGE id_project_status_history id_current_status INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEB0D1B111 FOREIGN KEY (id_current_status) REFERENCES project_status_history (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2FB3D0EEB0D1B111 ON project (id_current_status)');
        $this->addSql('ALTER TABLE project_status_history DROP FOREIGN KEY FK_C6DD336CF12E799E');
        $this->addSql('DROP INDEX IDX_C6DD336CF12E799E ON project_status_history');
        $this->addSql('ALTER TABLE project_status_history CHANGE status status INT NOT NULL, CHANGE id_project id_project INT NOT NULL');
        $this->addSql('ALTER TABLE project_status_history ADD CONSTRAINT FK_C6DD336C166D1F9C FOREIGN KEY (id_project) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_project_status_id_project ON project_status_history (id_project)');
        $this->addSql('ALTER TABLE clients_status_history DROP FOREIGN KEY FK_3E28AF07E173B1B8');
        $this->addSql('DROP INDEX idx_clients_status_history_id_status ON clients_status_history');
        $this->addSql('DROP INDEX id_client ON clients_status_history');
        $this->addSql('ALTER TABLE clients_status_history RENAME COLUMN id_client TO id_client');
        $this->addSql('ALTER TABLE clients_status_history RENAME COLUMN id_status TO status');
        $this->addSql("ALTER TABLE clients_status_history CHANGE added added DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('ALTER TABLE clients_status_history ADD CONSTRAINT FK_3E28AF07AB014612 FOREIGN KEY (id_client) REFERENCES clients (id_client) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_clients_status_id_client ON clients_status_history (id_client)');
        $this->addSql('CREATE INDEX idx_clients_status_status ON clients_status_history (status)');
        $this->addSql('ALTER TABLE clients_status_history RENAME TO clients_status');
        $this->addSql('ALTER TABLE project_status_history RENAME TO project_status');
        $this->addSql('ALTER TABLE project_status CHANGE status status SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE clients_status CHANGE status status SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE project_status RENAME INDEX idx_c6dd336c7b00651c TO idx_project_status_status');
        $this->addSql('ALTER TABLE project_status RENAME INDEX idx_c6dd336c699b6baf TO idx_project_status_added_by');
        $this->addSql('ALTER TABLE clients RENAME INDEX uniq_c82e74b0d1b111 TO UNIQ_C82E7441AF0274');
        $this->addSql('ALTER TABLE project RENAME INDEX uniq_2fb3d0eeb0d1b111 TO UNIQ_2FB3D0EE41AF0274');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE clients RENAME INDEX uniq_c82e7441af0274 TO UNIQ_C82E74B0D1B111');
        $this->addSql('ALTER TABLE project RENAME INDEX uniq_2fb3d0ee41af0274 TO UNIQ_2FB3D0EEB0D1B111');
        $this->addSql('ALTER TABLE project_status CHANGE status status INT NOT NULL');
        $this->addSql('ALTER TABLE clients_status CHANGE status status INT NOT NULL');
        $this->addSql('ALTER TABLE project_status RENAME INDEX idx_project_status_status TO IDX_C6DD336C7B00651C');
        $this->addSql('ALTER TABLE project_status RENAME INDEX idx_project_status_added_by TO IDX_C6DD336C699B6BAF');
        $this->addSql('ALTER TABLE clients_status RENAME TO clients_status_history');
        $this->addSql('ALTER TABLE project_status RENAME TO project_status_history');
        $this->addSql('CREATE TABLE clients_status (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, UNIQUE INDEX UNIQ_7ED7B1FBEA750E8 (label), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (10, 'A contrôler')");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (80, 'Clôturé (demande du prêteur)')");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (90, 'Clôturé (Unilend)')");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (20, 'Complétude')");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (30, 'Complétude (Relance)')");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (40, 'Complétude (Réponse)')");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (100, 'Compte soldé et définitivement fermé')");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (5, 'Création')");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (70, 'Désactivé')");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (50, 'Modification')");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (65, 'Suspendu')");
        $this->addSql("INSERT INTO clients_status (id, label) VALUES (60, 'Valide')");
        $this->addSql('ALTER TABLE clients DROP FOREIGN KEY FK_C82E74B0D1B111');
        $this->addSql('DROP INDEX UNIQ_C82E74B0D1B111 ON clients');
        $this->addSql('ALTER TABLE clients ADD id_client_status_history INT DEFAULT NULL, DROP id_current_status');
        $this->addSql('ALTER TABLE clients ADD CONSTRAINT FK_C82E74DF1ED241 FOREIGN KEY (id_client_status_history) REFERENCES clients_status_history (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_C82E74DF1ED241 ON clients (id_client_status_history)');
        $this->addSql('ALTER TABLE clients_status_history DROP FOREIGN KEY FK_3E28AF07AB014612');
        $this->addSql('DROP INDEX idx_clients_status_id_client ON clients_status_history');
        $this->addSql('DROP INDEX idx_clients_status_status ON clients_status_history');
        $this->addSql('ALTER TABLE clients_status_history RENAME COLUMN status TO id_status');
        $this->addSql('ALTER TABLE clients_status_history CHANGE added added DATETIME NOT NULL');
        $this->addSql('ALTER TABLE clients_status_history ADD CONSTRAINT FK_3E28AF075D37D0F1 FOREIGN KEY (id_status) REFERENCES clients_status (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE clients_status_history ADD CONSTRAINT FK_3E28AF07E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX idx_clients_status_history_id_status ON clients_status_history (id_status)');
        $this->addSql('CREATE INDEX id_client ON clients_status_history (id_client)');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEB0D1B111');
        $this->addSql('DROP INDEX UNIQ_2FB3D0EEB0D1B111 ON project');
        $this->addSql('ALTER TABLE project CHANGE id_current_status id_project_status_history INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEC60C84FB FOREIGN KEY (id_project_status_history) REFERENCES project_status_history (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2FB3D0EEC60C84FB ON project (id_project_status_history)');
        $this->addSql('ALTER TABLE project_status_history DROP FOREIGN KEY FK_C6DD336C166D1F9C');
        $this->addSql('DROP INDEX idx_project_status_id_project ON project_status_history');
        $this->addSql('ALTER TABLE project_status_history CHANGE status status SMALLINT NOT NULL, CHANGE id_project id_project INT NOT NULL');
        $this->addSql('ALTER TABLE project_status_history ADD CONSTRAINT FK_C6DD336CF12E799E FOREIGN KEY (id_project) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_C6DD336CF12E799E ON project_status_history (id_project)');
    }
}
