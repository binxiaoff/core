<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191104151859 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-486 (Upload attachment to existing projects)';
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE attachment CHANGE archived archived DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE project_attachment DROP FOREIGN KEY FK_61F9A289166D1F9C');
        $this->addSql('ALTER TABLE project_attachment DROP FOREIGN KEY FK_61F9A289464E68B');
        $this->addSql('DROP INDEX UNIQ_61F9A289464E68B ON project_attachment');
        $this->addSql('DROP INDEX IDX_61F9A289166D1F9C ON project_attachment');
        $this->addSql('ALTER TABLE project_attachment DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE project_attachment ADD id INT AUTO_INCREMENT NOT NULL PRIMARY KEY, RENAME COLUMN project_id TO id_project, RENAME COLUMN attachment_id TO id_attachment, ADD updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD added DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE project_attachment ADD CONSTRAINT FK_61F9A289F12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_attachment ADD CONSTRAINT FK_61F9A289DCD5596C FOREIGN KEY (id_attachment) REFERENCES attachment (id)');
        $this->addSql('CREATE INDEX IDX_61F9A289F12E799E ON project_attachment (id_project)');
        $this->addSql('CREATE INDEX IDX_61F9A289DCD5596C ON project_attachment (id_attachment)');
        $this->addSql('ALTER TABLE project_attachment CHANGE added added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_61F9A289F12E799EDCD5596C ON project_attachment (id_project, id_attachment)');
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_61F9A289F12E799EDCD5596C ON project_attachment');
        $this->addSql('ALTER TABLE project_attachment CHANGE added added DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE attachment CHANGE archived archived DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE project_attachment MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE project_attachment DROP FOREIGN KEY FK_61F9A289F12E799E');
        $this->addSql('ALTER TABLE project_attachment DROP FOREIGN KEY FK_61F9A289DCD5596C');
        $this->addSql('DROP INDEX IDX_61F9A289F12E799E ON project_attachment');
        $this->addSql('DROP INDEX IDX_61F9A289DCD5596C ON project_attachment');
        $this->addSql('ALTER TABLE project_attachment DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE project_attachment ADD project_id INT NOT NULL, ADD attachment_id INT NOT NULL, DROP id, DROP id_project, DROP id_attachment, DROP updated, DROP added');
        $this->addSql('ALTER TABLE project_attachment ADD CONSTRAINT FK_61F9A289166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_attachment ADD CONSTRAINT FK_61F9A289464E68B FOREIGN KEY (attachment_id) REFERENCES attachment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_61F9A289464E68B ON project_attachment (attachment_id)');
        $this->addSql('CREATE INDEX IDX_61F9A289166D1F9C ON project_attachment (project_id)');
        $this->addSql('ALTER TABLE project_attachment ADD PRIMARY KEY (project_id, attachment_id)');
    }
}
