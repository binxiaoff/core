<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210622143452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Agency] CALS-3998 Add agent confidential drive';
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

        $this->addSql('ALTER TABLE agency_agent ADD id_confidential_drive INT DEFAULT NULL');

        $agents = $this->connection->fetchAllAssociative('SELECT * FROM agency_agent');

        foreach ($agents as ['id' => $agentId]) {
            $this->addSql("INSERT INTO core_drive SELECT NULL, {$uuid}");
            $this->addSql("UPDATE agency_agent SET id_confidential_drive = (SELECT MAX(id) FROM core_drive) WHERE id = {$agentId}");
        }

        $this->addSql('CREATE UNIQUE INDEX UNIQ_284713B37754ACA8 ON agency_agent (id_confidential_drive)');
        $this->addSql('ALTER TABLE agency_agent ADD CONSTRAINT FK_284713B37754ACA8 FOREIGN KEY (id_confidential_drive) REFERENCES core_drive (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agency_agent MODIFY id_confidential_drive INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE agency_agent DROP FOREIGN KEY FK_284713B37754ACA8');
        $this->addSql('DROP INDEX UNIQ_284713B37754ACA8 ON agency_agent');
        $this->addSql('ALTER TABLE agency_agent DROP id_confidential_drive');
    }
}
