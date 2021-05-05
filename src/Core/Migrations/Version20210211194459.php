<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210211194459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-3300 Add term and publicationDate to covenant';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE agency_term (id INT AUTO_INCREMENT NOT NULL, id_covenant INT DEFAULT NULL, start DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', end DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_B208FB85B5B48B91 (public_id), INDEX IDX_B208FB85A4306C62 (id_covenant), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agency_term ADD CONSTRAINT FK_B208FB85A4306C62 FOREIGN KEY (id_covenant) REFERENCES agency_covenant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_covenant ADD publication_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE agency_term');
        $this->addSql('ALTER TABLE agency_covenant DROP publication_date');
    }
}
