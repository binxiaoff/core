<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210107092533 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create versions for the existing offers';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
INSERT INTO syndication_interest_reply_version (id_project_participation, added_by, interest_reply_added, interest_reply_money_amount, interest_reply_money_currency)
SELECT id, added_by, interest_reply_added, interest_reply_money_amount, interest_reply_money_currency
FROM syndication_project_participation
WHERE interest_reply_money_amount IS NOT NULL
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO syndication_invitation_reply_version (id_project_participation_tranche, id_project_participation_status, added_by, invitation_reply_added, invitation_reply_money_amount, invitation_reply_money_currency)
SELECT sppt.id, spp.id_current_status, sppt.added_by, sppt.invitation_reply_added, sppt.invitation_reply_money_amount, sppt.invitation_reply_money_currency
FROM syndication_project_participation_tranche sppt
INNER JOIN syndication_project_participation spp ON sppt.id_project_participation = spp.id
WHERE sppt.invitation_reply_money_amount IS NOT NULL
SQL);

    }

    public function down(Schema $schema) : void
    {
        $this->addSql('TRUNCATE TABLE syndication_interest_reply_version');
        $this->addSql('TRUNCATE TABLE syndication_invitation_reply_version');
    }
}
