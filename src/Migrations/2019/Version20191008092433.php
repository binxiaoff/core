<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191008092433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'TECH-146 (Add many to many between marketSegment and staff)';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $marketSegments = $this->connection->fetchAll('SELECT id, label FROM market_segment');
        $marketSegments = array_column($marketSegments, 'label', 'id');
        $marketSegments = array_flip($marketSegments);
        $staffs         = $this->connection->fetchAll('SELECT id, roles FROM staff');

        $this->addSql('CREATE TABLE staff_market_segment (staff_id INT NOT NULL, market_segment_id INT NOT NULL, INDEX IDX_523D18F2D4D57CD (staff_id), INDEX IDX_523D18F2B5D73EB1 (market_segment_id), PRIMARY KEY(staff_id, market_segment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE staff_market_segment ADD CONSTRAINT FK_523D18F2D4D57CD FOREIGN KEY (staff_id) REFERENCES staff (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE staff_market_segment ADD CONSTRAINT FK_523D18F2B5D73EB1 FOREIGN KEY (market_segment_id) REFERENCES market_segment (id) ON DELETE CASCADE');

        foreach ($staffs as $staff) {
            $id    = $staff['id'];
            $roles = json_decode($staff['roles'], true, 512, JSON_THROW_ON_ERROR);

            foreach ($roles as $role) {
                if (false !== mb_strpos($role, '_STAFF_MARKET_')) {
                    $marketSegment = str_replace('ROLE_STAFF_MARKET_', '', $role);
                    $marketSegment = mb_strtolower($marketSegment);
                    $this->addSql("INSERT INTO staff_market_segment VALUES ({$id}, {$marketSegments[$marketSegment]})");
                    $this->addSql("UPDATE staff SET roles = JSON_REMOVE(roles, JSON_UNQUOTE(JSON_SEARCH(roles, 'one', '" . $role . "'))) WHERE id = " . $id);
                }
            }
        }
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $data = $this->connection->fetchAll('SELECT * from staff_market_segment');

        $marketSegments = $this->connection->fetchAll('SELECT id, label FROM market_segment');
        $marketSegments = array_column($marketSegments, 'label', 'id');

        foreach ($data as $datum) {
            $staffId         = $datum['staff_id'];
            $marketSegmentId = $datum['market_segment_id'];
            $role            = 'ROLE_STAFF_MARKET_' . mb_strtoupper($marketSegments[$marketSegmentId]);

            $this->addSql("UPDATE staff SET roles = JSON_ARRAY_APPEND(roles, '$', '{$role}') WHERE id = {$staffId}");
        }

        $this->addSql('DROP TABLE staff_market_segment');
    }
}
