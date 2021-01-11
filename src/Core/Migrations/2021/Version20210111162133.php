<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210111162133 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Archive undecided participation for project in allocation';
    }

    /**
     * @param Schema $schema
     * @throws DBALException
     */
    public function up(Schema $schema) : void
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

        $fixableParticipations = $this->connection->fetchAll(
            <<<SQL
    SELECT spp.id as id
    FROM syndication_project_participation spp
    INNER JOIN syndication_project_participation_status spps on spp.id_current_status = spps.id
    INNER JOIN syndication_project sp on spp.id_project = sp.id
    INNER JOIN syndication_project_status sps on sp.id_current_status = sps.id
    INNER JOIN core_staff cs on sp.id_company_submitter = cs.id_company AND sp.id_user_submitter = cs.id_user
    WHERE (spps.status = 10 OR spps.status = 20)
    AND sps.status = 40
SQL
        );

        foreach ($fixableParticipations as ['id' => $fixableParticipation]) {
            $idStaff = $this->connection->fetchColumn(
                <<<SQL
            SELECT cs.id
            FROM core_staff cs
            INNER JOIN syndication_project sp ON sp.id_company_submitter = cs.id_company AND sp.id_user_submitter = cs.id_user
            INNER JOIN syndication_project_participation spp on sp.id = spp.id_project
            WHERE spp.id = $fixableParticipation
SQL
            );

            $this->addSql("INSERT INTO syndication_project_participation_status VALUES (NULL, $fixableParticipation, $idStaff, -20, NOW(), $uuid)");

            $this->addSql(<<<SQL
        UPDATE syndication_project_participation
        SET syndication_project_participation.id_current_status = LAST_INSERT_ID()
        WHERE id = $fixableParticipation
SQL
);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // Data migration
        $this->skipIf(true);
    }
}
