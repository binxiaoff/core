<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210122111219 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-3181 : Rename and drop INDEX';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_4FBF094FDA33CDFB ON core_company');
        $this->addSql('ALTER TABLE core_message RENAME INDEX uniq_b6bd307fb5b48b91 TO UNIQ_A4AA854CB5B48B91');
        $this->addSql('ALTER TABLE core_message RENAME INDEX idx_b6bd307f3b616c8a TO IDX_A4AA854C3B616C8A');
        $this->addSql('ALTER TABLE core_message RENAME INDEX idx_b6bd307f7937ff22 TO IDX_A4AA854C7937FF22');
        $this->addSql('ALTER TABLE core_message_file RENAME INDEX uniq_250aadc9b5b48b91 TO UNIQ_88BA1411B5B48B91');
        $this->addSql('ALTER TABLE core_message_file RENAME INDEX idx_250aadc97bf2a12 TO IDX_88BA14117BF2A12');
        $this->addSql('ALTER TABLE core_message_file RENAME INDEX idx_250aadc96820990f TO IDX_88BA14116820990F');
        $this->addSql('ALTER TABLE core_message_status RENAME INDEX idx_4c27f8136820990f TO IDX_B77B119C6820990F');
        $this->addSql('ALTER TABLE core_message_status RENAME INDEX idx_4c27f813e831476e TO IDX_B77B119CE831476E');
        $this->addSql('ALTER TABLE core_message_thread RENAME INDEX uniq_607d18cb5b48b91 TO UNIQ_FD5B3803B5B48B91');
        $this->addSql('ALTER TABLE core_message_thread RENAME INDEX uniq_607d18cae73e249 TO UNIQ_FD5B3803AE73E249');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE core_message RENAME INDEX idx_a4aa854c3b616c8a TO IDX_B6BD307F3B616C8A');
        $this->addSql('ALTER TABLE core_message RENAME INDEX idx_a4aa854c7937ff22 TO IDX_B6BD307F7937FF22');
        $this->addSql('ALTER TABLE core_message RENAME INDEX uniq_a4aa854cb5b48b91 TO UNIQ_B6BD307FB5B48B91');
        $this->addSql('ALTER TABLE core_message_file RENAME INDEX idx_88ba14116820990f TO IDX_250AADC96820990F');
        $this->addSql('ALTER TABLE core_message_file RENAME INDEX idx_88ba14117bf2a12 TO IDX_250AADC97BF2A12');
        $this->addSql('ALTER TABLE core_message_file RENAME INDEX uniq_88ba1411b5b48b91 TO UNIQ_250AADC9B5B48B91');
        $this->addSql('ALTER TABLE core_message_status RENAME INDEX idx_b77b119c6820990f TO IDX_4C27F8136820990F');
        $this->addSql('ALTER TABLE core_message_status RENAME INDEX idx_b77b119ce831476e TO IDX_4C27F813E831476E');
        $this->addSql('ALTER TABLE core_message_thread RENAME INDEX uniq_fd5b3803ae73e249 TO UNIQ_607D18CAE73E249');
        $this->addSql('ALTER TABLE core_message_thread RENAME INDEX uniq_fd5b3803b5b48b91 TO UNIQ_607D18CB5B48B91');
    }
}
