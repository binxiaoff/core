<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210702131956 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-3639 add dataroom drive on existing reservations';
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

        $reservations = $this->connection->executeQuery('SELECT id FROM credit_guaranty_reservation WHERE id_drive = 0')->fetchAllAssociative();
        foreach ($reservations as $reservation) {
            $this->addSql("INSERT INTO core_drive (public_id) VALUE ({$uuid})");
            $this->addSql('UPDATE credit_guaranty_reservation SET id_drive = LAST_INSERT_ID() WHERE id = ' . $reservation['id']);
        }
        $this->addSql('ALTER TABLE credit_guaranty_reservation ADD CONSTRAINT FK_DE8BB8578698B4BF FOREIGN KEY (id_drive) REFERENCES core_drive (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DE8BB8578698B4BF ON credit_guaranty_reservation (id_drive)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_reservation DROP FOREIGN KEY FK_DE8BB8578698B4BF');
        $this->addSql('DROP INDEX UNIQ_DE8BB8578698B4BF ON credit_guaranty_reservation');
    }
}
