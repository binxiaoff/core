<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210317092337 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return '[Agency] Update model for project members';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE agency_borrower_member (id INT AUTO_INCREMENT NOT NULL, id_user INT DEFAULT NULL, id_borrower INT DEFAULT NULL, type VARCHAR(255) NOT NULL, referent TINYINT(1) NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_5B36A3AAB5B48B91 (public_id), INDEX IDX_5B36A3AA6B3CA4B (id_user), INDEX IDX_5B36A3AA8B4BA121 (id_borrower), UNIQUE INDEX UNIQ_5B36A3AA6B3CA4B8B4BA121 (id_user, id_borrower), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE agency_participation_member (id INT AUTO_INCREMENT NOT NULL, id_user INT DEFAULT NULL, id_participation INT DEFAULT NULL, type VARCHAR(255) NOT NULL, referent TINYINT(1) NOT NULL, public_id VARCHAR(36) NOT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_D4BCDFFBB5B48B91 (public_id), INDEX IDX_D4BCDFFB6B3CA4B (id_user), INDEX IDX_D4BCDFFB157D332A (id_participation), UNIQUE INDEX UNIQ_D4BCDFFB6B3CA4B157D332A (id_user, id_participation), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agency_borrower_member ADD CONSTRAINT FK_5B36A3AA6B3CA4B FOREIGN KEY (id_user) REFERENCES core_user (id)');
        $this->addSql('ALTER TABLE agency_borrower_member ADD CONSTRAINT FK_5B36A3AA8B4BA121 FOREIGN KEY (id_borrower) REFERENCES agency_borrower (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_participation_member ADD CONSTRAINT FK_D4BCDFFB6B3CA4B FOREIGN KEY (id_user) REFERENCES core_user (id)');
        $this->addSql('ALTER TABLE agency_participation_member ADD CONSTRAINT FK_D4BCDFFB157D332A FOREIGN KEY (id_participation) REFERENCES agency_participation (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE agency_contact');
        $this->addSql('ALTER TABLE agency_borrower ADD id_signatory INT DEFAULT NULL, ADD id_referent INT DEFAULT NULL, DROP signatory_first_name, DROP signatory_last_name, DROP signatory_email, DROP referent_first_name, DROP referent_last_name, DROP referent_email');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4F2B0DC78F FOREIGN KEY (id_signatory) REFERENCES agency_borrower_member (id)');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4FAE4140F9 FOREIGN KEY (id_referent) REFERENCES agency_borrower_member (id)');
        $this->addSql('CREATE INDEX IDX_C78A2C4F2B0DC78F ON agency_borrower (id_signatory)');
        $this->addSql('CREATE INDEX IDX_C78A2C4FAE4140F9 ON agency_borrower (id_referent)');
        $this->addSql('ALTER TABLE agency_zz_versioned_project CHANGE object_class object_class VARCHAR(191) NOT NULL, CHANGE username username VARCHAR(191) DEFAULT NULL');
        $this->addSql('ALTER TABLE core_zz_versioned_user CHANGE object_class object_class VARCHAR(191) NOT NULL, CHANGE username username VARCHAR(191) DEFAULT NULL');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project CHANGE object_class object_class VARCHAR(191) NOT NULL, CHANGE username username VARCHAR(191) DEFAULT NULL');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_comment CHANGE object_class object_class VARCHAR(191) NOT NULL, CHANGE username username VARCHAR(191) DEFAULT NULL');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation CHANGE object_class object_class VARCHAR(191) NOT NULL, CHANGE username username VARCHAR(191) DEFAULT NULL');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation_tranche CHANGE object_class object_class VARCHAR(191) NOT NULL, CHANGE username username VARCHAR(191) DEFAULT NULL');
        $this->addSql('ALTER TABLE syndication_zz_versioned_tranche CHANGE object_class object_class VARCHAR(191) NOT NULL, CHANGE username username VARCHAR(191) DEFAULT NULL');

        $this->addSql('ALTER TABLE agency_borrower_member DROP type, DROP referent');
        $this->addSql('ALTER TABLE agency_participation_member DROP type, DROP referent');

        $this->addSql('ALTER TABLE agency_participation ADD id_referent INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_participation ADD CONSTRAINT FK_E0ED689EAE4140F9 FOREIGN KEY (id_referent) REFERENCES agency_participation_member (id)');
        $this->addSql('CREATE INDEX IDX_E0ED689EAE4140F9 ON agency_participation (id_referent)');

        $this->addSql('ALTER TABLE agency_borrower DROP FOREIGN KEY FK_C78A2C4F2B0DC78F');
        $this->addSql('ALTER TABLE agency_borrower DROP FOREIGN KEY FK_C78A2C4FAE4140F9');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4F2B0DC78F FOREIGN KEY (id_signatory) REFERENCES agency_borrower_member (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4FAE4140F9 FOREIGN KEY (id_referent) REFERENCES agency_borrower_member (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE agency_participation DROP FOREIGN KEY FK_E0ED689EAE4140F9');
        $this->addSql('ALTER TABLE agency_participation ADD CONSTRAINT FK_E0ED689EAE4140F9 FOREIGN KEY (id_referent) REFERENCES agency_participation_member (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE agency_participation_member ADD type VARCHAR(40) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE agency_participation_member DROP type');

        $this->addSql('ALTER TABLE agency_borrower DROP FOREIGN KEY FK_C78A2C4F2B0DC78F');
        $this->addSql('ALTER TABLE agency_borrower DROP FOREIGN KEY FK_C78A2C4FAE4140F9');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4F2B0DC78F FOREIGN KEY (id_signatory) REFERENCES agency_borrower_member (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE agency_borrower ADD CONSTRAINT FK_C78A2C4FAE4140F9 FOREIGN KEY (id_referent) REFERENCES agency_borrower_member (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE agency_participation DROP FOREIGN KEY FK_E0ED689EAE4140F9');
        $this->addSql('ALTER TABLE agency_participation ADD CONSTRAINT FK_E0ED689EAE4140F9 FOREIGN KEY (id_referent) REFERENCES agency_participation_member (id) ON UPDATE NO ACTION ON DELETE NO ACTION');

        $this->addSql('ALTER TABLE agency_participation DROP FOREIGN KEY FK_E0ED689EAE4140F9');
        $this->addSql('DROP INDEX IDX_E0ED689EAE4140F9 ON agency_participation');
        $this->addSql('ALTER TABLE agency_participation DROP id_referent');

        $this->addSql('ALTER TABLE agency_borrower_member ADD type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD referent TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE agency_participation_member ADD type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD referent TINYINT(1) NOT NULL');

        $this->addSql('ALTER TABLE agency_borrower DROP FOREIGN KEY FK_C78A2C4F2B0DC78F');
        $this->addSql('ALTER TABLE agency_borrower DROP FOREIGN KEY FK_C78A2C4FAE4140F9');
        $this->addSql('CREATE TABLE agency_contact (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, added_by INT NOT NULL, updated_by INT DEFAULT NULL, type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, first_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, last_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, department VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, occupation VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, phone VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, referent TINYINT(1) NOT NULL, public_id VARCHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3A627F6916FE72E1 (updated_by), INDEX IDX_3A627F69699B6BAF (added_by), INDEX IDX_3A627F69F12E799E (id_project), UNIQUE INDEX UNIQ_3A627F69B5B48B91 (public_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE agency_contact ADD CONSTRAINT FK_3A627F6916FE72E1 FOREIGN KEY (updated_by) REFERENCES core_staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE agency_contact ADD CONSTRAINT FK_3A627F69699B6BAF FOREIGN KEY (added_by) REFERENCES core_staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE agency_contact ADD CONSTRAINT FK_3A627F69F12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE agency_borrower_member');
        $this->addSql('DROP TABLE agency_participation_member');
        $this->addSql('DROP INDEX IDX_C78A2C4F2B0DC78F ON agency_borrower');
        $this->addSql('DROP INDEX IDX_C78A2C4FAE4140F9 ON agency_borrower');
        $this->addSql('ALTER TABLE agency_borrower ADD signatory_first_name VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD signatory_last_name VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD signatory_email VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD referent_first_name VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD referent_last_name VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ADD referent_email VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, DROP id_signatory, DROP id_referent');
        $this->addSql('ALTER TABLE agency_zz_versioned_project CHANGE object_class object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE username username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE core_zz_versioned_user CHANGE object_class object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE username username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project CHANGE object_class object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE username username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_comment CHANGE object_class object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE username username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation CHANGE object_class object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE username username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation_tranche CHANGE object_class object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE username username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE syndication_zz_versioned_tranche CHANGE object_class object_class VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE username username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
