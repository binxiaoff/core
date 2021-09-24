<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210211162552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3299 Add Inequality embeddable and create MarginImpect && MarginRule';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE agency_margin_impact (id INT AUTO_INCREMENT NOT NULL, id_margin_rule INT DEFAULT NULL, id_tranche INT DEFAULT NULL, margin NUMERIC(5, 4) NOT NULL, public_id VARCHAR(36) NOT NULL, UNIQUE INDEX UNIQ_BE66DFA3B5B48B91 (public_id), INDEX IDX_BE66DFA32384C64D (id_margin_rule), INDEX IDX_BE66DFA3B8FAF130 (id_tranche), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE agency_margin_rule (id INT AUTO_INCREMENT NOT NULL, id_covenant INT DEFAULT NULL, public_id VARCHAR(36) NOT NULL, inequality_operator VARCHAR(2) NOT NULL, inequality_value NUMERIC(65, 4) NOT NULL, inequality_max_value NUMERIC(65, 4) DEFAULT NULL, UNIQUE INDEX UNIQ_383340BB5B48B91 (public_id), INDEX IDX_383340BA4306C62 (id_covenant), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agency_margin_impact ADD CONSTRAINT FK_BE66DFA32384C64D FOREIGN KEY (id_margin_rule) REFERENCES agency_margin_rule (id)');
        $this->addSql('ALTER TABLE agency_margin_impact ADD CONSTRAINT FK_BE66DFA3B8FAF130 FOREIGN KEY (id_tranche) REFERENCES agency_tranche (id)');
        $this->addSql('ALTER TABLE agency_margin_rule ADD CONSTRAINT FK_383340BA4306C62 FOREIGN KEY (id_covenant) REFERENCES agency_covenant (id)');
        $this->addSql('ALTER TABLE agency_covenant_rule ADD inequality_operator VARCHAR(2) NOT NULL, ADD inequality_value NUMERIC(65, 4) NOT NULL, ADD inequality_max_value NUMERIC(65, 4) DEFAULT NULL, DROP expression');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_margin_impact DROP FOREIGN KEY FK_BE66DFA32384C64D');
        $this->addSql('DROP TABLE agency_margin_impact');
        $this->addSql('DROP TABLE agency_margin_rule');
        $this->addSql('ALTER TABLE agency_covenant_rule ADD expression VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, DROP inequality_operator, DROP inequality_value, DROP inequality_max_value');
    }
}
