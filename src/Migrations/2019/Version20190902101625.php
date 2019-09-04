<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190902101625 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Remove useless data from clients table';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE clients DROP preferred_name, DROP date_of_birth, DROP id_birth_country, DROP birth_city, DROP id_nationaliy, DROP type');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE clients ADD preferred_name VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, ADD date_of_birth DATE DEFAULT NULL, ADD id_birth_country INT DEFAULT NULL, ADD birth_city VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, ADD id_nationaliy INT DEFAULT NULL, ADD type SMALLINT DEFAULT NULL');
    }
}
