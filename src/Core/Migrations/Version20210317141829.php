<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210317141829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3556 Apply DoctrineExtensions changes';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_zz_versioned_user CHANGE object_class object_class VARCHAR(191) NOT NULL, CHANGE username username VARCHAR(191) DEFAULT NULL');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project CHANGE object_class object_class VARCHAR(191) NOT NULL, CHANGE username username VARCHAR(191) DEFAULT NULL');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_comment CHANGE object_class object_class VARCHAR(191) NOT NULL, CHANGE username username VARCHAR(191) DEFAULT NULL');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation CHANGE object_class object_class VARCHAR(191) NOT NULL, CHANGE username username VARCHAR(191) DEFAULT NULL');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation_tranche CHANGE object_class object_class VARCHAR(191) NOT NULL, CHANGE username username VARCHAR(191) DEFAULT NULL');
        $this->addSql('ALTER TABLE syndication_zz_versioned_tranche CHANGE object_class object_class VARCHAR(191) NOT NULL, CHANGE username username VARCHAR(191) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_zz_versioned_user CHANGE object_class object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE username username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project CHANGE object_class object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE username username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_comment CHANGE object_class object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE username username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation CHANGE object_class object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE username username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation_tranche CHANGE object_class object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE username username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE syndication_zz_versioned_tranche CHANGE object_class object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE username username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
