<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190618091538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALSTECH-4 Remove clients_adresses table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE clients_adresses');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE clients_adresses (id_adresse INT AUTO_INCREMENT NOT NULL, id_client INT NOT NULL, defaut INT NOT NULL, type INT NOT NULL, nom_adresse VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, civilite VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, nom VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, prenom VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, societe VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, adresse1 VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, adresse2 VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, adresse3 VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, cp VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, ville VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, id_pays INT DEFAULT NULL, telephone VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, mobile VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, commentaire MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, meme_adresse_fiscal TINYINT(1) DEFAULT NULL, adresse_fiscal VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, ville_fiscal VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, cp_fiscal VARCHAR(191) DEFAULT NULL COLLATE utf8mb4_unicode_ci, id_pays_fiscal INT DEFAULT NULL, status INT DEFAULT NULL, added DATETIME NOT NULL, updated DATETIME DEFAULT NULL, INDEX id_client (id_client), INDEX type (type), INDEX defaut (defaut), PRIMARY KEY(id_adresse)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE clients_adresses ADD CONSTRAINT FK_7F985363E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
