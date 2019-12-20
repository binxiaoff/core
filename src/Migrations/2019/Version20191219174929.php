<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191219174929 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Transform ROLE_COMPANY_OWNER into DUTY_STAFF_ADMIN';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $json = json_encode(['DUTY_STAFF_ADMIN'], JSON_THROW_ON_ERROR, 512);
        $this->addSql("UPDATE staff SET roles = '{$json}' ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $json = json_encode(['ROLE_COMPANY_OWNER'], JSON_THROW_ON_ERROR, 512);
        $this->addSql("UPDATE staff SET roles = '{$json}' ");
    }
}
