<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201130100833 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2924 Prefix syndication tables';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('RENAME TABLE interest_reply_version TO syndication_interest_reply_version');
        $this->addSql('RENAME TABLE invitation_reply_version TO syndication_invitation_reply_version');
        $this->addSql('RENAME TABLE project TO syndication_project');
        $this->addSql('RENAME TABLE project_comment TO syndication_project_comment');
        $this->addSql('RENAME TABLE project_file TO syndication_project_file');
        $this->addSql('RENAME TABLE project_message TO syndication_project_message');
        $this->addSql('RENAME TABLE project_organizer TO syndication_project_organizer');
        $this->addSql('RENAME TABLE project_participation TO syndication_project_participation');
        $this->addSql('RENAME TABLE project_participation_member TO syndication_project_participation_member');
        $this->addSql('RENAME TABLE project_participation_status TO syndication_project_participation_status');
        $this->addSql('RENAME TABLE project_participation_tranche TO syndication_project_participation_tranche');
        $this->addSql('RENAME TABLE project_status TO syndication_project_status');
        $this->addSql('RENAME TABLE project_tag TO syndication_project_tag');
        $this->addSql('RENAME TABLE tag TO syndication_tag');
        $this->addSql('RENAME TABLE tranche TO syndication_tranche');

        $this->addSql('RENAME TABLE zz_versioned_project TO syndication_zz_versioned_project');
        $this->addSql('RENAME TABLE zz_versioned_project_comment TO syndication_zz_versioned_project_comment');
        $this->addSql('RENAME TABLE zz_versioned_project_participation TO syndication_zz_versioned_project_participation');
        $this->addSql('RENAME TABLE zz_versioned_project_participation_tranche TO syndication_zz_versioned_project_participation_tranche');
        $this->addSql('RENAME TABLE zz_versioned_tranche TO syndication_zz_versioned_tranche');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('RENAME TABLE syndication_interest_reply_version TO interest_reply_version');
        $this->addSql('RENAME TABLE syndication_invitation_reply_version TO invitation_reply_version');
        $this->addSql('RENAME TABLE syndication_project TO project');
        $this->addSql('RENAME TABLE syndication_project_comment TO project_comment');
        $this->addSql('RENAME TABLE syndication_project_file TO project_file');
        $this->addSql('RENAME TABLE syndication_project_message TO project_message');
        $this->addSql('RENAME TABLE syndication_project_organizer TO project_organizer');
        $this->addSql('RENAME TABLE syndication_project_participation TO project_participation');
        $this->addSql('RENAME TABLE syndication_project_participation_member TO project_participation_member');
        $this->addSql('RENAME TABLE syndication_project_participation_status TO project_participation_status');
        $this->addSql('RENAME TABLE syndication_project_participation_tranche TO project_participation_tranche');
        $this->addSql('RENAME TABLE syndication_project_status TO project_status');
        $this->addSql('RENAME TABLE syndication_project_tag TO project_tag');
        $this->addSql('RENAME TABLE syndication_tag TO tag');
        $this->addSql('RENAME TABLE syndication_tranche TO tranche');

        $this->addSql('RENAME TABLE syndication_zz_versioned_project TO zz_versioned_project');
        $this->addSql('RENAME TABLE syndication_zz_versioned_project_comment TO zz_versioned_project_comment');
        $this->addSql('RENAME TABLE syndication_zz_versioned_project_participation TO zz_versioned_project_participation');
        $this->addSql('RENAME TABLE syndication_zz_versioned_project_participation_tranche TO zz_versioned_project_participation_tranche');
        $this->addSql('RENAME TABLE syndication_zz_versioned_tranche TO zz_versioned_tranche');
    }
}
