<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200213135717 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-973 Rename companies to company';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE companies TO company');

        $this->addSql('ALTER TABLE company RENAME INDEX uniq_8244aa3ada33cdfb TO UNIQ_4FBF094FDA33CDFB');
        $this->addSql('ALTER TABLE company RENAME INDEX uniq_8244aa3a17d2fe0d TO UNIQ_4FBF094F17D2FE0D');
        $this->addSql('ALTER TABLE company RENAME INDEX uniq_8244aa3ab5b48b91 TO UNIQ_4FBF094FB5B48B91');
        $this->addSql('ALTER TABLE company RENAME INDEX idx_8244aa3a91c00f TO IDX_4FBF094F91C00F');
        $this->addSql('ALTER TABLE company RENAME INDEX uniq_8244aa3a41af0274 TO UNIQ_4FBF094F41AF0274');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE company TO companies');

        $this->addSql('ALTER TABLE companies RENAME INDEX idx_4fbf094f91c00f TO IDX_8244AA3A91C00F');
        $this->addSql('ALTER TABLE companies RENAME INDEX uniq_4fbf094f17d2fe0d TO UNIQ_8244AA3A17D2FE0D');
        $this->addSql('ALTER TABLE companies RENAME INDEX uniq_4fbf094f41af0274 TO UNIQ_8244AA3A41AF0274');
        $this->addSql('ALTER TABLE companies RENAME INDEX uniq_4fbf094fb5b48b91 TO UNIQ_8244AA3AB5B48B91');
        $this->addSql('ALTER TABLE companies RENAME INDEX uniq_4fbf094fda33cdfb TO UNIQ_8244AA3ADA33CDFB');
    }
}
