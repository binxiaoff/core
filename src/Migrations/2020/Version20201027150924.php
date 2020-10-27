<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201027150924 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE project_participation_tranche_history (id INT AUTO_INCREMENT NOT NULL, project_participation_tranche_id INT NOT NULL, added_by INT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', invitation_reply_added DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', invitation_reply_money_amount NUMERIC(15, 2) DEFAULT NULL, invitation_reply_money_currency VARCHAR(3) DEFAULT NULL, INDEX IDX_94310C52F91A2387 (project_participation_tranche_id), INDEX IDX_94310C52699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_participation_tranche_history ADD CONSTRAINT FK_94310C52F91A2387 FOREIGN KEY (project_participation_tranche_id) REFERENCES project_participation_tranche (id)');
        $this->addSql('ALTER TABLE project_participation_tranche_history ADD CONSTRAINT FK_94310C52699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE project_participation_tranche_history');
    }
}
