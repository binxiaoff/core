<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200106113547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-699 add public id on companies, project_participation and project_participation_contact table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE companies ADD public_id VARCHAR(36) NOT NULL');
        $this->addSql('UPDATE companies set public_id = UUID()');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8244AA3AB5B48B91 ON companies (public_id)');

        $this->addSql('ALTER TABLE project_participation ADD public_id VARCHAR(36) NOT NULL');
        $this->addSql('UPDATE project_participation set public_id = UUID()');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7FC47549B5B48B91 ON project_participation (public_id)');

        $this->addSql('ALTER TABLE project_participation_contact ADD public_id VARCHAR(36) NOT NULL');
        $this->addSql('UPDATE project_participation_contact set public_id = UUID()');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_41530AB3B5B48B91 ON project_participation_contact (public_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_8244AA3AB5B48B91 ON companies');
        $this->addSql('ALTER TABLE companies DROP public_id');

        $this->addSql('DROP INDEX UNIQ_7FC47549B5B48B91 ON project_participation');
        $this->addSql('ALTER TABLE project_participation DROP public_id');

        $this->addSql('DROP INDEX UNIQ_41530AB3B5B48B91 ON project_participation_contact');
        $this->addSql('ALTER TABLE project_participation_contact DROP public_id');
    }
}
