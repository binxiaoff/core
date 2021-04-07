<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210407154149 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-3672 Add reservation_duration to credit_guaranty_program';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_guaranty_program ADD reservation_duration SMALLINT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE credit_guaranty_program DROP reservation_duration');
    }
}
