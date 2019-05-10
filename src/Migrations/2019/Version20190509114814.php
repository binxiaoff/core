<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190509114814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CLAS-149 add rating score on project. Define new project entity as FK of comment';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project ADD internal_rating_score VARCHAR(8) DEFAULT NULL AFTER title');
        $this->addSql('ALTER TABLE project_comment DROP FOREIGN KEY FK_26A5E09F12E799E');
        $this->addSql('ALTER TABLE project_comment ADD CONSTRAINT FK_26A5E09F12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project DROP internal_rating_score');
        $this->addSql('ALTER TABLE project_comment DROP FOREIGN KEY FK_26A5E09F12E799E');
        $this->addSql('ALTER TABLE project_comment ADD CONSTRAINT FK_26A5E09F12E799E FOREIGN KEY (id_project) REFERENCES projects (id_project) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
