<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210305155955 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] Update covenant model';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_covenant ADD contract_article VARCHAR(500) DEFAULT NULL, ADD contract_extract VARCHAR(500) DEFAULT NULL, ADD recurrence VARCHAR(255) DEFAULT NULL, DROP article, DROP extract, DROP periodicity, CHANGE name name VARCHAR(50) NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_margin_impact DROP FOREIGN KEY FK_BE66DFA3B8FAF130');
        $this->addSql('ALTER TABLE agency_margin_impact ADD CONSTRAINT FK_BE66DFA3B8FAF130 FOREIGN KEY (id_tranche) REFERENCES agency_tranche (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_term ADD start_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD end_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP start, DROP end');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_covenant ADD extract VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD periodicity VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, DROP contract_article, DROP contract_extract, CHANGE name name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE recurrence article VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE agency_margin_impact DROP FOREIGN KEY FK_BE66DFA3B8FAF130');
        $this->addSql('ALTER TABLE agency_margin_impact ADD CONSTRAINT FK_BE66DFA3B8FAF130 FOREIGN KEY (id_tranche) REFERENCES agency_tranche (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE agency_term ADD start DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD end DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP start_date, DROP end_date');
    }
}
