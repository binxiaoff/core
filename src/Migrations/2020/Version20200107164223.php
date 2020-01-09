<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200107164223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-714 Add ProjectMessage';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE project_message (id INT AUTO_INCREMENT NOT NULL, participation_id INT NOT NULL, added_by INT NOT NULL, content LONGTEXT NOT NULL, archived DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_20A33C1A6ACE3B73 (participation_id), INDEX IDX_20A33C1A699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_message ADD CONSTRAINT FK_20A33C1A6ACE3B73 FOREIGN KEY (participation_id) REFERENCES project_participation (id)');
        $this->addSql('ALTER TABLE project_message ADD CONSTRAINT FK_20A33C1A699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE project_message ADD public_id VARCHAR(36) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_20A33C1AB5B48B91 ON project_message (public_id)');
        $this->addSql('ALTER TABLE project_message DROP FOREIGN KEY FK_20A33C1A6ACE3B73');
        $this->addSql('DROP INDEX IDX_20A33C1A6ACE3B73 ON project_message');
        $this->addSql('ALTER TABLE project_message CHANGE participation_id id_participation INT NOT NULL');
        $this->addSql('ALTER TABLE project_message ADD CONSTRAINT FK_20A33C1A157D332A FOREIGN KEY (id_participation) REFERENCES project_participation (id)');
        $this->addSql('CREATE INDEX IDX_20A33C1A157D332A ON project_message (id_participation)');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE project_message');
    }
}
