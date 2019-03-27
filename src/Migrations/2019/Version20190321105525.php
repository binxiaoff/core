<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190321105525 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-64 commission';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE project_percent_fee (id_percent_fee INT NOT NULL, id_project INT NOT NULL, INDEX IDX_F7D17EEF270C44E3 (id_percent_fee), INDEX IDX_F7D17EEFF12E799E (id_project), PRIMARY KEY(id_percent_fee, id_project)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_percent_fee ADD CONSTRAINT FK_F7D17EEF270C44E3 FOREIGN KEY (id_percent_fee) REFERENCES percent_fee (id)');
        $this->addSql('ALTER TABLE project_percent_fee ADD CONSTRAINT FK_F7D17EEFF12E799E FOREIGN KEY (id_project) REFERENCES projects (id_project)');
        $this->addSql('INSERT INTO translations (locale, section, name, translation, added, updated)
                            VALUES (\'fr_FR\', \'fee-type\', \'document_fee\', \'Commission de mise en place\', NOW(), NULL),
                                   (\'fr_FR\', \'fee-type\', \'running_fee\', \'Frais de gestion running\', NOW(), NULL);');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE project_percent_fee');
        $this->addSql('DELETE FROM translations WHERE translations.section = \'fee-type\'');
    }
}
