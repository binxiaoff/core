<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190426141553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-104 create new project_status_history table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE project_status_history (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, status SMALLINT NOT NULL, added DATETIME NOT NULL, added_by INT NOT NULL, INDEX IDX_C6DD336CF12E799E (id_project), INDEX IDX_C6DD336C699B6BAF (added_by), INDEX IDX_C6DD336C7B00651C (status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_status_history ADD CONSTRAINT FK_C6DD336CF12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_status_history ADD CONSTRAINT FK_C6DD336CE7CA843C FOREIGN KEY (added_by) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEC60C84FB');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEC60C84FB FOREIGN KEY (id_project_status_history) REFERENCES project_status_history (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEC60C84FB');
        $this->addSql('DROP TABLE project_status_history');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEC60C84FB');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEC60C84FB FOREIGN KEY (id_project_status_history) REFERENCES projects_status_history (id_project_status_history) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
