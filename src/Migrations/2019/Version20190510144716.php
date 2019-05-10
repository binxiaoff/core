<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190510144716 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Fix project comment entity foreign key';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE project_comment DROP FOREIGN KEY FK_26A5E09F12E799E');
        $this->addSql('ALTER TABLE project_comment ADD CONSTRAINT FK_26A5E09F12E799E FOREIGN KEY (id_project) REFERENCES project (id)');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE project_comment DROP FOREIGN KEY FK_26A5E09F12E799E');
        $this->addSql('ALTER TABLE project_comment ADD CONSTRAINT FK_26A5E09F12E799E FOREIGN KEY (id_project) REFERENCES projects (id_project) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
