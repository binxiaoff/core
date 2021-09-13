<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210209091519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3295 Add covenants table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE agency_covenant (id INT AUTO_INCREMENT NOT NULL, id_project INT DEFAULT NULL, name VARCHAR(255) NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', article VARCHAR(255) DEFAULT NULL, extract VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, nature VARCHAR(255) NOT NULL, startDate DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delay SMALLINT NOT NULL, endDate DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', periodicity VARCHAR(255) NOT NULL, public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_E8F1E10CB5B48B91 (public_id), INDEX IDX_E8F1E10CF12E799E (id_project), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agency_covenant ADD CONSTRAINT FK_E8F1E10CF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE agency_covenant');
    }
}
