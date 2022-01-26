<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220106160405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-4501 Alter table core_mail_queue to fit its new usage.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE core_mail_queue RENAME COLUMN id_queue TO id');
        $this->addSql('ALTER TABLE core_mail_queue ADD transport VARCHAR(100) NOT NULL, DROP scheduled_at, DROP hash, MODIFY id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE core_mail_queue ADD message_id VARCHAR(60) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_mail_log_message_id ON core_mail_queue (message_id)');
        $this->addSql('RENAME TABLE core_mail_queue TO core_mail_log');
        $this->addSql('ALTER TABLE core_mail_log DROP error_message');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE core_mail_log TO core_mail_queue');
        $this->addSql('ALTER TABLE core_mail_log ADD error_message TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE core_mail_queue RENAME COLUMN id TO id_queue');
        $this->addSql('ALTER TABLE core_mail_queue ADD scheduled_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD hash VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, DROP transport, MODIFY id_queue INT NOT NULL');
        $this->addSql('DROP INDEX idx_mail_log_message_id ON core_mail_queue');
        $this->addSql('ALTER TABLE core_mail_queue DROP message_id');
    }
}
