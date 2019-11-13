<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191106150210 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-429 (ProjectParticipation list)';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_participation ADD project_participation_fee_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project_participation ADD CONSTRAINT FK_7FC47549F7C31B40 FOREIGN KEY (project_participation_fee_id) REFERENCES project_participation_fee (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7FC47549F7C31B40 ON project_participation (project_participation_fee_id)');
        $this->addSql('ALTER TABLE project_participation_fee DROP INDEX IDX_28BEA4AE73E249, ADD UNIQUE INDEX UNIQ_28BEA4AE73E249 (id_project_participation)');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_participation DROP FOREIGN KEY FK_7FC47549F7C31B40');
        $this->addSql('DROP INDEX UNIQ_7FC47549F7C31B40 ON project_participation');
        $this->addSql('ALTER TABLE project_participation DROP project_participation_fee_id');
        $this->addSql('ALTER TABLE project_participation_fee DROP INDEX UNIQ_28BEA4AE73E249, ADD INDEX IDX_28BEA4AE73E249 (id_project_participation)');
    }
}
