<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210127150744 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-2117 [Agency] Add participations';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE agency_participation (id INT AUTO_INCREMENT NOT NULL, id_project INT NOT NULL, id_participant INT NOT NULL, participant_commission NUMERIC(5, 4) DEFAULT NULL, responsibilities INT NOT NULL COMMENT \'(DC2Type:bitmask)\', agent_commission NUMERIC(5, 4) DEFAULT NULL, arranger_commission NUMERIC(5, 4) DEFAULT NULL, deputy_arranger_commission NUMERIC(5, 4) DEFAULT NULL, prorata TINYINT(1) NOT NULL, secondary TINYINT(1) NOT NULL, public_id VARCHAR(36) NOT NULL, final_allocation_amount NUMERIC(15, 2) NOT NULL, final_allocation_currency VARCHAR(3) NOT NULL, UNIQUE INDEX UNIQ_E0ED689EB5B48B91 (public_id), INDEX IDX_E0ED689EF12E799E (id_project), INDEX IDX_E0ED689ECF8DA6E6 (id_participant), UNIQUE INDEX UNIQ_E0ED689EF12E799ECF8DA6E6 (id_project, id_participant), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE agency_participation_tranche_allocation (id INT AUTO_INCREMENT NOT NULL, id_participation INT NOT NULL, id_tranche INT NOT NULL, allocation_amount NUMERIC(15, 2) NOT NULL, allocation_currency VARCHAR(3) NOT NULL, INDEX IDX_9E1BC289157D332A (id_participation), INDEX IDX_9E1BC289B8FAF130 (id_tranche), UNIQUE INDEX UNIQ_9E1BC289157D332AB8FAF130 (id_participation, id_tranche), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agency_participation ADD CONSTRAINT FK_E0ED689EF12E799E FOREIGN KEY (id_project) REFERENCES agency_project (id)');
        $this->addSql('ALTER TABLE agency_participation ADD CONSTRAINT FK_E0ED689ECF8DA6E6 FOREIGN KEY (id_participant) REFERENCES core_company (id)');
        $this->addSql('ALTER TABLE agency_participation_tranche_allocation ADD CONSTRAINT FK_9E1BC289157D332A FOREIGN KEY (id_participation) REFERENCES agency_participation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_participation_tranche_allocation ADD CONSTRAINT FK_9E1BC289B8FAF130 FOREIGN KEY (id_tranche) REFERENCES agency_tranche (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agency_participation_tranche_allocation DROP FOREIGN KEY FK_9E1BC289157D332A');
        $this->addSql('DROP TABLE agency_participation');
        $this->addSql('DROP TABLE agency_participation_tranche_allocation');
    }
}
