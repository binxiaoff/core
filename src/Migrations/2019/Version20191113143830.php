<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191113143830 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename zz_versioned_project_fee table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('RENAME TABLE zz_versioned_project_fee TO zz_versioned_project_participation_fee');
        $this->addSql('ALTER TABLE zz_versioned_project_participation_fee RENAME INDEX idx_43f67dd1a78d87a7 TO IDX_EA6ACCF7A78D87A7');
        $this->addSql('ALTER TABLE zz_versioned_project_participation_fee RENAME INDEX idx_43f67dd1f85e0677 TO IDX_EA6ACCF7F85E0677');
        $this->addSql('ALTER TABLE zz_versioned_project_participation_fee RENAME INDEX idx_43f67dd1232d562b69684d7dbf1cd3c3 TO IDX_EA6ACCF7232D562B69684D7DBF1CD3C3');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('RENAME TABLE zz_versioned_project_participation_fee TO zz_versioned_project_fee');
        $this->addSql('ALTER TABLE zz_versioned_project_participation_fee RENAME INDEX idx_ea6accf7a78d87a7 TO IDX_43F67DD1A78D87A7');
        $this->addSql('ALTER TABLE zz_versioned_project_participation_fee RENAME INDEX idx_ea6accf7f85e0677 TO IDX_43F67DD1F85E0677');
        $this->addSql('ALTER TABLE zz_versioned_project_participation_fee RENAME INDEX idx_ea6accf7232d562b69684d7dbf1cd3c3 TO IDX_43F67DD1232D562B69684D7DBF1CD3C3');
    }
}
