<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210805132256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Hubspot] CALS-4259 modified columns name';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_hubspot_contact DROP FOREIGN KEY FK_5AA6EE19A76ED395');
        $this->addSql('DROP INDEX UNIQ_5AA6EE19A76ED395 ON core_hubspot_contact');
        $this->addSql('ALTER TABLE core_hubspot_contact ADD synchronized DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP updated, CHANGE user_id id_user INT DEFAULT NULL, CHANGE contact_id id_contact INT NOT NULL');
        $this->addSql('ALTER TABLE core_hubspot_contact ADD CONSTRAINT FK_5AA6EE196B3CA4B FOREIGN KEY (id_user) REFERENCES core_user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5AA6EE196B3CA4B ON core_hubspot_contact (id_user)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_hubspot_contact DROP FOREIGN KEY FK_5AA6EE196B3CA4B');
        $this->addSql('DROP INDEX UNIQ_5AA6EE196B3CA4B ON core_hubspot_contact');
        $this->addSql('ALTER TABLE core_hubspot_contact ADD updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP synchronized, CHANGE id_user user_id INT DEFAULT NULL, CHANGE id_contact contact_id INT NOT NULL');
        $this->addSql('ALTER TABLE core_hubspot_contact ADD CONSTRAINT FK_5AA6EE19A76ED395 FOREIGN KEY (user_id) REFERENCES core_user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5AA6EE19A76ED395 ON core_hubspot_contact (user_id)');
    }
}
