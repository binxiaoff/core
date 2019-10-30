<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191025153153 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-464 (Update failed login logging)';
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE client_failed_login (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(191) DEFAULT NULL, IP VARCHAR(191) DEFAULT NULL, retour VARCHAR(191) DEFAULT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_client_failed_login_username (username), INDEX idx_client_failed_login_ip (ip), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('DROP TABLE login_log');
    }

    /**
     * @param Schema $schema
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE login_log (id_log_login INT AUTO_INCREMENT NOT NULL, pseudo VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, IP VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, retour VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, added DATETIME NOT NULL, INDEX pseudo (pseudo), INDEX idx_login_log_IP (IP), PRIMARY KEY(id_log_login)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE client_failed_login');
    }
}
