<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210601085331 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-3287 [CreditGuaranty] add comment field in credit_guaranty_reservation_status table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_reservation_status ADD comment LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE credit_guaranty_reservation_status DROP comment');
    }
}
