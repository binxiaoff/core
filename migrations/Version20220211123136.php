<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220211123136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Arrangement] CALS-5671 Add publicId for projectStatus';
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

        $this->addSql('ALTER TABLE syndication_project_status ADD public_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('UPDATE syndication_project_status SET public_id =' . $uuid );
        $this->addSql('ALTER TABLE syndication_project_status MODIFY public_id VARCHAR(36) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2011913DB5B48B91 ON syndication_project_status (public_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_2011913DB5B48B91 ON syndication_project_status');
        $this->addSql('ALTER TABLE syndication_project_status DROP public_id');
    }
}
