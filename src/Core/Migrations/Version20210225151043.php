<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210225151043 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] Add missing elements';
    }

    public function up(Schema $schema): void
    {
        $uuid = "LOWER(
            CONCAT(
                HEX(RANDOM_BYTES(4)), '-',
                HEX(RANDOM_BYTES(2)), '-', 
                '4', SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3), '-', 
                CONCAT(HEX(FLOOR(ASCII(RANDOM_BYTES(1)) / 64)+8), SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3)), '-',
                HEX(RANDOM_BYTES(6))
            )
        )";

        $this->addSql('ALTER TABLE agency_borrower_tranche_share DROP CONSTRAINT FK_1B75C8DD8B4BA121, DROP CONSTRAINT FK_1B75C8DDB8FAF130, CHANGE id_borrower id_borrower INT NOT NULL, CHANGE id_tranche id_tranche INT NOT NULL');
        $this->addSql('ALTER TABLE agency_borrower_tranche_share ADD CONSTRAINT FK_1B75C8DD8B4BA121 FOREIGN KEY (id_borrower) REFERENCES agency_borrower (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_borrower_tranche_share ADD CONSTRAINT FK_1B75C8DDB8FAF130 FOREIGN KEY (id_tranche) REFERENCES agency_tranche (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_participation_tranche_allocation ADD public_id VARCHAR(36) NOT NULL');
        $this->addSql("UPDATE agency_participation_tranche_allocation SET public_id = {$uuid} WHERE public_id IS NULL");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9E1BC289B5B48B91 ON agency_participation_tranche_allocation (public_id)');
        $this->addSql('ALTER TABLE agency_project DROP agent_registration_city');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_borrower_tranche_share DROP CONSTRAINT FK_1B75C8DD8B4BA121, DROP CONSTRAINT FK_1B75C8DDB8FAF130, CHANGE id_borrower id_borrower INT DEFAULT NULL, CHANGE id_tranche id_tranche INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agency_borrower_tranche_share ADD CONSTRAINT FK_1B75C8DD8B4BA121 FOREIGN KEY (id_borrower) REFERENCES agency_borrower (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_borrower_tranche_share ADD CONSTRAINT FK_1B75C8DDB8FAF130 FOREIGN KEY (id_tranche) REFERENCES agency_tranche (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX UNIQ_9E1BC289B5B48B91 ON agency_participation_tranche_allocation');
        $this->addSql('ALTER TABLE agency_participation_tranche_allocation DROP public_id');
        $this->addSql('ALTER TABLE agency_project ADD agent_registration_city VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
