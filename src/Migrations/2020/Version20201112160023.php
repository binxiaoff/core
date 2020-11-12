<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201112160023 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2736 Historize interest reply';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE interest_reply_version (id INT AUTO_INCREMENT NOT NULL, id_project_participation INT NOT NULL, added_by INT NOT NULL, interest_reply_added DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', interest_reply_money_amount NUMERIC(15, 2) DEFAULT NULL, interest_reply_money_currency VARCHAR(3) DEFAULT NULL, INDEX IDX_CD6CFEDFAE73E249 (id_project_participation), INDEX IDX_CD6CFEDF699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE interest_reply_version ADD CONSTRAINT FK_CD6CFEDFAE73E249 FOREIGN KEY (id_project_participation) REFERENCES project_participation (id)');
        $this->addSql('ALTER TABLE interest_reply_version ADD CONSTRAINT FK_CD6CFEDF699B6BAF FOREIGN KEY (added_by) REFERENCES staff (id)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE interest_reply_version');
    }
}
