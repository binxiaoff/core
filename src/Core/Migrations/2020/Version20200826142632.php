<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200826142632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE staff DROP FOREIGN KEY FK_426EF39241AF0274');
        $this->addSql('ALTER TABLE staff ADD CONSTRAINT FK_426EF39241AF0274 FOREIGN KEY (id_current_status) REFERENCES staff_status (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE staff DROP FOREIGN KEY FK_426EF39241AF0274');
        $this->addSql('ALTER TABLE staff ADD CONSTRAINT FK_426EF39241AF0274 FOREIGN KEY (id_current_status) REFERENCES staff_status (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
