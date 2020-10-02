<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200701141345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE project_participation_contact TO project_participation_member');
        $this->addSql('ALTER TABLE project_participation_member RENAME INDEX uniq_41530ab3b5b48b91 TO UNIQ_2C624FF2B5B48B91');
        $this->addSql('ALTER TABLE project_participation_member RENAME INDEX idx_41530ab3ae73e249 TO IDX_2C624FF2AE73E249');
        $this->addSql('ALTER TABLE project_participation_member RENAME INDEX idx_41530ab3acebb2a2 TO IDX_2C624FF2ACEBB2A2');
        $this->addSql('ALTER TABLE project_participation_member RENAME INDEX idx_41530ab3efc7ea74 TO IDX_2C624FF2EFC7EA74');
        $this->addSql('ALTER TABLE project_participation_member RENAME INDEX idx_41530ab3699b6baf TO IDX_2C624FF2699B6BAF');
        $this->addSql('ALTER TABLE project_participation_member RENAME INDEX idx_41530ab351b07d6d TO IDX_2C624FF251B07D6D');
        $this->addSql('ALTER TABLE project_participation_member RENAME INDEX uniq_41530ab3acebb2a2ae73e249 TO UNIQ_2C624FF2ACEBB2A2AE73E249');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE project_participation_member TO project_participation_contact');
        $this->addSql('ALTER TABLE project_participation_member RENAME INDEX idx_2c624ff251b07d6d TO IDX_41530AB351B07D6D');
        $this->addSql('ALTER TABLE project_participation_member RENAME INDEX idx_2c624ff2699b6baf TO IDX_41530AB3699B6BAF');
        $this->addSql('ALTER TABLE project_participation_member RENAME INDEX idx_2c624ff2acebb2a2 TO IDX_41530AB3ACEBB2A2');
        $this->addSql('ALTER TABLE project_participation_member RENAME INDEX idx_2c624ff2ae73e249 TO IDX_41530AB3AE73E249');
        $this->addSql('ALTER TABLE project_participation_member RENAME INDEX idx_2c624ff2efc7ea74 TO IDX_41530AB3EFC7EA74');
        $this->addSql('ALTER TABLE project_participation_member RENAME INDEX uniq_2c624ff2acebb2a2ae73e249 TO UNIQ_41530AB3ACEBB2A2AE73E249');
        $this->addSql('ALTER TABLE project_participation_member RENAME INDEX uniq_2c624ff2b5b48b91 TO UNIQ_41530AB3B5B48B91');
    }
}
