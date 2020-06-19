<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200611094709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-1535 add invitation_reply_mode, simplify fee';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_participation ADD invitation_reply_mode VARCHAR(10) DEFAULT NULL AFTER invitation_request_fee_rate, DROP interest_request_fee_type, DROP interest_request_fee_comment, DROP interest_request_fee_recurring, DROP invitation_request_fee_type, DROP invitation_request_fee_recurring, DROP invitation_request_fee_comment, DROP allocation_fee_type, DROP allocation_fee_comment, DROP allocation_fee_recurring');

        // Ajuste the order of the columns
        $this->addSql('alter table project_participation modify public_id varchar(36) not null after id');
        $this->addSql('alter table project_participation modify committee_status varchar(30) null after invitation_reply_mode');
        $this->addSql('alter table project_participation modify committee_deadline date null comment \'(DC2Type:date_immutable)\' after committee_status');
        $this->addSql('alter table project_participation modify committee_comment longtext null after committee_deadline');
        $this->addSql('alter table project_participation modify participant_last_consulted datetime null comment \'(DC2Type:datetime_immutable)\' after allocation_fee_rate');
        $this->addSql('alter table project_participation modify added datetime not null comment \'(DC2Type:datetime_immutable)\' after participant_last_consulted');
        $this->addSql('alter table project_participation modify added_by int not null after added');
        $this->addSql('alter table project_participation modify updated datetime null comment \'(DC2Type:datetime_immutable)\' after added_by');
        $this->addSql('alter table project_participation modify interest_request_fee_rate decimal(5,4) null after interest_request_money_currency');
        $this->addSql('alter table project_participation modify interest_request_added datetime null comment \'(DC2Type:datetime_immutable)\' after interest_request_fee_rate');
        $this->addSql('alter table project_participation modify interest_reply_added datetime null comment \'(DC2Type:datetime_immutable)\' after interest_reply_money_currency');
        $this->addSql('alter table project_participation modify invitation_request_money_amount decimal(15,2) null after interest_reply_added');
        $this->addSql('alter table project_participation modify invitation_request_money_currency varchar(3) null after invitation_request_money_amount');
        $this->addSql('alter table project_participation modify invitation_request_added datetime null comment \'(DC2Type:datetime_immutable)\' after invitation_request_fee_rate');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE project_participation ADD interest_request_fee_type VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD interest_request_fee_comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD interest_request_fee_recurring TINYINT(1) DEFAULT NULL, ADD invitation_request_fee_type VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD invitation_request_fee_recurring TINYINT(1) DEFAULT NULL, ADD invitation_request_fee_comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD allocation_fee_type VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD allocation_fee_comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD allocation_fee_recurring TINYINT(1) DEFAULT NULL, DROP invitation_reply_mode');
    }
}
