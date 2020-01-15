<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200115160442 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-751 Download entity';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE download (id INT AUTO_INCREMENT NOT NULL, id_attachment INT NOT NULL, id_clients INT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_781A8270DCD5596C (id_attachment), INDEX IDX_781A8270DE558704 (id_clients), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE download ADD CONSTRAINT FK_781A8270DCD5596C FOREIGN KEY (id_attachment) REFERENCES attachment (id)');
        $this->addSql('ALTER TABLE download ADD CONSTRAINT FK_781A8270DE558704 FOREIGN KEY (id_clients) REFERENCES clients (id_client)');
        $this->addSql('ALTER TABLE attachment DROP downloaded');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE download');
        $this->addSql('ALTER TABLE attachment ADD downloaded DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
