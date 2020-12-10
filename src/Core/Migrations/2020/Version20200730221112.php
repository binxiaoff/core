<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ramsey\Uuid\Uuid;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200730221112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-2037 add public id to staff status';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE staff_status ADD public_id VARCHAR(36) NOT NULL');
        $statuses = $this->connection->fetchAll('SELECT id FROM staff_status');
        $statuses = array_column($statuses, 'id');
        foreach ($statuses as $statusId) {
            $uuid = (Uuid::uuid4())->toString();
            $this->addSql("UPDATE staff_status SET public_id = '{$uuid}' WHERE id = {$statusId}");
        }
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7E7DD7A7B5B48B91 ON staff_status (public_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_7E7DD7A7B5B48B91 ON staff_status');
        $this->addSql('ALTER TABLE staff_status DROP public_id');
    }
}
