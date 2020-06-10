<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;
use Ramsey\Uuid\Uuid;

final class Version20200610155517 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1181 Add public_id to Tranche (PublicizeIdentityTrait)';
    }

    /**
     * @param Schema $schema
     *
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tranche ADD public_id VARCHAR(36) NOT NULL');
        $ids = array_column($this->connection->fetchAll('SELECT id FROM tranche'), 'id');
        foreach ($ids as $id) {
            $publicId = (string) (Uuid::uuid4());
            $this->addSql("UPDATE tranche SET public_id = '{$publicId}' WHERE id = {$id}");
        }
        $this->addSql('CREATE UNIQUE INDEX UNIQ_66675840B5B48B91 ON tranche (public_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_66675840B5B48B91 ON tranche');
        $this->addSql('ALTER TABLE tranche DROP public_id');
    }
}
