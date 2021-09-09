<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210628144322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3272 add the dataroom drive on the existing programs';
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     */
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

        $programs = $this->connection->executeQuery('SELECT id FROM credit_guaranty_program WHERE id_drive = 0')->fetchAllAssociative();
        foreach ($programs as $program) {
            $this->addSql("INSERT INTO core_drive (public_id) VALUE ({$uuid})");
            $this->addSql('UPDATE credit_guaranty_program SET id_drive = LAST_INSERT_ID() WHERE id = ' . $program['id']);
        }
        $this->addSql('ALTER TABLE credit_guaranty_program ADD CONSTRAINT FK_190C774F8698B4BF FOREIGN KEY (id_drive) REFERENCES core_drive (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_190C774F8698B4BF ON credit_guaranty_program (id_drive)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_program DROP FOREIGN KEY FK_190C774F8698B4BF');
        $this->addSql('DROP INDEX UNIQ_190C774F8698B4BF ON credit_guaranty_program');
    }
}
