<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201027150924 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2707 Create ProjectParticipationTrancheVersion';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE project_participation_tranche_version (id INT AUTO_INCREMENT NOT NULL, id_project_participation_tranche INT NOT NULL, added_by INT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', invitation_reply_added DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', invitation_reply_money_amount NUMERIC(15, 2) DEFAULT NULL, invitation_reply_money_currency VARCHAR(3) DEFAULT NULL, UNIQUE INDEX UNIQ_C97AFDAF263895D (id_project_participation_tranche), INDEX IDX_C97AFDA699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_participation_tranche_version ADD CONSTRAINT FK_C97AFDAF263895D FOREIGN KEY (id_project_participation_tranche) REFERENCES project_participation_tranche (id)');
        $this->addSql('ALTER TABLE project_participation_tranche_version ADD CONSTRAINT FK_C97AFDA699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE project_participation_tranche_version');
    }
}
