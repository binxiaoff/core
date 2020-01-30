<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200130161133 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-474 Add staff logging';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE staff_log (id INT AUTO_INCREMENT NOT NULL, added_by INT NOT NULL, staff_id INT NOT NULL, previous_roles JSON NOT NULL, previous_market_segment JSON NOT NULL, added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_133F30C699B6BAF (added_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE staff_log ADD CONSTRAINT FK_133F30C699B6BAF FOREIGN KEY (added_by) REFERENCES clients (id_client)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE staff_log');
    }
}
