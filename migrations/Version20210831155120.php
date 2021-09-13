<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210831155120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[CreditGuaranty] CALS-3652 hydrate id_borrower and id_project in reservation';
    }

    public function up(Schema $schema): void
    {
        $reservationsWithoutBorrower = $this->connection->executeQuery('SELECT id FROM credit_guaranty_reservation WHERE id_borrower = NULL')->fetchAllAssociative();
        $reservationsWithoutProject  = $this->connection->executeQuery('SELECT id FROM credit_guaranty_reservation WHERE id_project = NULL')->fetchAllAssociative();

        $uuid = "LOWER(
            CONCAT(
                HEX(RANDOM_BYTES(4)), '-',
                HEX(RANDOM_BYTES(2)), '-',
                '4', SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3), '-',
                CONCAT(HEX(FLOOR(ASCII(RANDOM_BYTES(1)) / 64)+8), SUBSTR(HEX(RANDOM_BYTES(2)), 2, 3)), '-',
                HEX(RANDOM_BYTES(6))
            )
        )";

        foreach ($reservationsWithoutBorrower as $reservation) {
            $this->addSql("INSERT INTO credit_guaranty_borrower (public_id) VALUE ({$uuid})");
            $this->addSql('UPDATE credit_guaranty_reservation SET id_borrower = LAST_INSERT_ID() WHERE id = ' . $reservation['id']);
        }
        foreach ($reservationsWithoutProject as $reservation) {
            $this->addSql("INSERT INTO credit_guaranty_project (public_id) VALUE ({$uuid})");
            $this->addSql('UPDATE credit_guaranty_reservation SET id_project = LAST_INSERT_ID() WHERE id = ' . $reservation['id']);
        }
    }
}
