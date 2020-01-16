<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200116092533 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-751 Log attachment downloads';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE attachment_download (id INT AUTO_INCREMENT NOT NULL, id_attachment INT NOT NULL, id_client INT NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7C093130DCD5596C (id_attachment), INDEX IDX_7C093130E173B1B8 (id_client), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE attachment_download ADD CONSTRAINT FK_7C093130DCD5596C FOREIGN KEY (id_attachment) REFERENCES attachment (id)');
        $this->addSql('ALTER TABLE attachment_download ADD CONSTRAINT FK_7C093130E173B1B8 FOREIGN KEY (id_client) REFERENCES clients (id_client)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE attachment_download');
    }
}
