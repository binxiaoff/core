<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200610130726 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Add nullability to new fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project CHANGE privileged_contact_first_name privileged_contact_first_name VARCHAR(255) DEFAULT NULL, CHANGE privileged_contact_last_name privileged_contact_last_name VARCHAR(255) DEFAULT NULL, CHANGE privileged_contact_parent_unit privileged_contact_parent_unit VARCHAR(255) DEFAULT NULL, CHANGE privileged_contact_occupation privileged_contact_occupation VARCHAR(255) DEFAULT NULL, CHANGE privileged_contact_email privileged_contact_email VARCHAR(255) DEFAULT NULL, CHANGE privileged_contact_phone privileged_contact_phone VARCHAR(35) DEFAULT NULL, CHANGE target_arranger_participation_money_amount target_arranger_participation_money_amount NUMERIC(15, 2) DEFAULT NULL, CHANGE target_arranger_participation_money_currency target_arranger_participation_money_currency VARCHAR(3) DEFAULT NULL, CHANGE arrangement_commission_money_amount arrangement_commission_money_amount NUMERIC(15, 2) DEFAULT NULL, CHANGE arrangement_commission_money_currency arrangement_commission_money_currency VARCHAR(3) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project CHANGE target_arranger_participation_money_amount target_arranger_participation_money_amount NUMERIC(15, 2) NOT NULL, CHANGE target_arranger_participation_money_currency target_arranger_participation_money_currency VARCHAR(3) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE arrangement_commission_money_amount arrangement_commission_money_amount NUMERIC(15, 2) NOT NULL, CHANGE arrangement_commission_money_currency arrangement_commission_money_currency VARCHAR(3) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE privileged_contact_first_name privileged_contact_first_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE privileged_contact_last_name privileged_contact_last_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE privileged_contact_parent_unit privileged_contact_parent_unit VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE privileged_contact_occupation privileged_contact_occupation VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE privileged_contact_email privileged_contact_email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE privileged_contact_phone privileged_contact_phone VARCHAR(35) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
