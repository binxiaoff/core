<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191127180233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-508 Replace id company owner by id project';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB36B9957C');
        $this->addSql('DROP INDEX IDX_795FD9BB36B9957C ON attachment');
        $this->addSql('ALTER TABLE attachment CHANGE id_company_owner id_project INT NOT NULL');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BBF12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
        $this->addSql('CREATE INDEX IDX_795FD9BBF12E799E ON attachment (id_project)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BBF12E799E');
        $this->addSql('DROP INDEX IDX_795FD9BBF12E799E ON attachment');
        $this->addSql('ALTER TABLE attachment CHANGE id_project id_company_owner INT DEFAULT NULL');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB36B9957C FOREIGN KEY (id_company_owner) REFERENCES companies (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_795FD9BB36B9957C ON attachment (id_company_owner)');
    }
}
