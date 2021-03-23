<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210323152412 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Transform covenant related datetime properties into date properties';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE agency_covenant CHANGE startDate startDate DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE endDate endDate DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE publication_date publication_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE agency_term CHANGE sharing_date sharing_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE archiving_date archiving_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE start_date start_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE end_date end_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE validation_date validation_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE agency_zz_versioned_project CHANGE object_class object_class VARCHAR(191) NOT NULL, CHANGE username username VARCHAR(191) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE agency_covenant CHANGE startDate startDate DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE endDate endDate DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE publication_date publication_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE agency_term CHANGE start_date start_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE end_date end_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE validation_date validation_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE sharing_date sharing_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE archiving_date archiving_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE agency_zz_versioned_project CHANGE object_class object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE username username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
