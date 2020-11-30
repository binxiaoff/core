<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201127154620 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2925 Prefix core tables';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('RENAME TABLE acceptations_legal_docs TO core_acceptations_legal_docs');
        $this->addSql('RENAME TABLE client_failed_login TO core_client_failed_login');
        $this->addSql('RENAME TABLE clients TO core_clients');
        $this->addSql('RENAME TABLE client_status TO core_client_status');
        $this->addSql('RENAME TABLE client_successful_login TO core_client_successful_login');
        $this->addSql('RENAME TABLE company TO core_company');
        $this->addSql('RENAME TABLE company_module TO core_company_module');
        $this->addSql('RENAME TABLE company_module_log TO core_company_module_log');
        $this->addSql('RENAME TABLE company_status TO core_company_status');
        $this->addSql('RENAME TABLE file TO core_file');
        $this->addSql('RENAME TABLE file_download TO core_file_download');
        $this->addSql('RENAME TABLE file_version TO core_file_version');
        $this->addSql('RENAME TABLE file_version_signature TO core_file_version_signature');
        $this->addSql('RENAME TABLE legal_document TO core_legal_document');
        $this->addSql('RENAME TABLE mail_queue TO core_mail_queue');
        $this->addSql('RENAME TABLE market_segment TO core_market_segment');
        $this->addSql('RENAME TABLE staff TO core_staff');
        $this->addSql('RENAME TABLE staff_log TO core_staff_log');
        $this->addSql('RENAME TABLE staff_status TO core_staff_status');
        $this->addSql('RENAME TABLE staff_market_segment TO core_staff_market_segment');
        $this->addSql('RENAME TABLE temporary_token TO core_temporary_token');
        $this->addSql('RENAME TABLE user_agent TO core_user_agent');

        $this->addSql('RENAME TABLE zz_versioned_clients TO core_zz_versioned_clients');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('RENAME TABLE core_acceptations_legal_docs TO acceptations_legal_docs');
        $this->addSql('RENAME TABLE core_client_failed_login TO client_failed_login');
        $this->addSql('RENAME TABLE core_client_status TO client_status');
        $this->addSql('RENAME TABLE core_client_successful_login TO client_successful_login');
        $this->addSql('RENAME TABLE core_clients TO clients');
        $this->addSql('RENAME TABLE core_company TO company');
        $this->addSql('RENAME TABLE core_company_module TO company_module');
        $this->addSql('RENAME TABLE core_company_module_log TO company_module_log');
        $this->addSql('RENAME TABLE core_company_status TO company_status');
        $this->addSql('RENAME TABLE core_file TO file');
        $this->addSql('RENAME TABLE core_file_download TO file_download');
        $this->addSql('RENAME TABLE core_file_version TO file_version');
        $this->addSql('RENAME TABLE core_file_version_signature TO file_version_signature');
        $this->addSql('RENAME TABLE core_legal_document TO legal_document');
        $this->addSql('RENAME TABLE core_mail_queue TO mail_queue');
        $this->addSql('RENAME TABLE core_market_segment TO market_segment');
        $this->addSql('RENAME TABLE core_staff TO staff');
        $this->addSql('RENAME TABLE core_staff_log TO staff_log');
        $this->addSql('RENAME TABLE core_staff_status TO staff_status');
        $this->addSql('RENAME TABLE core_staff_market_segment TO staff_market_segment');
        $this->addSql('RENAME TABLE core_temporary_token TO temporary_token');
        $this->addSql('RENAME TABLE core_user_agent TO user_agent');

        $this->addSql('RENAME TABLE zz_versioned_clients TO core_zz_versioned_clients');
    }
}
