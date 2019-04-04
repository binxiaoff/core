<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190403123604 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Add new project comment types';
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE TABLE project_comment (id INT AUTO_INCREMENT NOT NULL, id_parent INT DEFAULT NULL, id_project INT NOT NULL, id_client INT NOT NULL, content MEDIUMTEXT NOT NULL, visibility INT NOT NULL, added DATETIME NOT NULL, updated DATETIME DEFAULT NULL, INDEX IDX_26A5E091BB9D5A2 (id_parent), INDEX IDX_26A5E09F12E799E (id_project), INDEX IDX_26A5E09E173B1B8 (id_client), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_comment ADD CONSTRAINT FK_26A5E091BB9D5A2 FOREIGN KEY (id_parent) REFERENCES project_comment (id)');
        $this->addSql('ALTER TABLE project_comment ADD CONSTRAINT FK_26A5E09F12E799E FOREIGN KEY (id_project) REFERENCES projects (id_project)');
        $this->addSql('ALTER TABLE project_comment ADD CONSTRAINT FK_26A5E09E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE projects CHANGE comments description MEDIUMTEXT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE project_comment DROP FOREIGN KEY FK_26A5E091BB9D5A2');
        $this->addSql('DROP TABLE project_comment');
        $this->addSql('ALTER TABLE projects CHANGE description comments MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci');
    }
}
