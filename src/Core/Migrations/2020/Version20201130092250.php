<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201130092250 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-2925 Rename indexes';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE core_acceptations_legal_docs RENAME INDEX uniq_f1d2e432b5b48b91 TO UNIQ_4F817FFFB5B48B91');
        $this->addSql('ALTER TABLE core_acceptations_legal_docs RENAME INDEX idx_f1d2e4327f757bbc TO IDX_4F817FFF7F757BBC');
        $this->addSql('ALTER TABLE core_acceptations_legal_docs RENAME INDEX idx_f1d2e432bd57fa7c TO IDX_4F817FFFBD57FA7C');
        $this->addSql('ALTER TABLE core_acceptations_legal_docs RENAME INDEX uniq_f1d2e4327f757bbcbd57fa7c TO UNIQ_4F817FFF7F757BBCBD57FA7C');
        $this->addSql('ALTER TABLE core_client_successful_login RENAME INDEX idx_94e06715e173b1b8 TO IDX_2AB3FCD8E173B1B8');
        $this->addSql('ALTER TABLE core_client_successful_login RENAME INDEX idx_94e0671561c3e712 TO IDX_2AB3FCD861C3E712');
        $this->addSql('DROP INDEX IDX_C82E74D1B862B8 ON core_clients');
        $this->addSql('ALTER TABLE core_clients RENAME INDEX uniq_c82e74e7927c74 TO UNIQ_12DF9B47E7927C74');
        $this->addSql('ALTER TABLE core_clients RENAME INDEX uniq_c82e74b5b48b91 TO UNIQ_12DF9B47B5B48B91');
        $this->addSql('ALTER TABLE core_clients RENAME INDEX uniq_c82e7441af0274 TO UNIQ_12DF9B4741AF0274');
        $this->addSql('ALTER TABLE core_clients RENAME INDEX idx_c82e74c808ba5a TO IDX_12DF9B47C808BA5A');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5DA8BC7CDA33CDFB ON core_company (email_domain)');
        $this->addSql('ALTER TABLE core_company RENAME INDEX uniq_4fbf094fdb8bba08 TO UNIQ_5DA8BC7CDB8BBA08');
        $this->addSql('ALTER TABLE core_company RENAME INDEX uniq_4fbf094fdd756216 TO UNIQ_5DA8BC7CDD756216');
        $this->addSql('ALTER TABLE core_company RENAME INDEX uniq_4fbf094f8910b08d TO UNIQ_5DA8BC7C8910B08D');
        $this->addSql('ALTER TABLE core_company RENAME INDEX uniq_4fbf094f17d2fe0d TO UNIQ_5DA8BC7C17D2FE0D');
        $this->addSql('ALTER TABLE core_company RENAME INDEX uniq_4fbf094fb5b48b91 TO UNIQ_5DA8BC7CB5B48B91');
        $this->addSql('ALTER TABLE core_company RENAME INDEX idx_4fbf094f91c00f TO IDX_5DA8BC7C91C00F');
        $this->addSql('ALTER TABLE core_company RENAME INDEX uniq_4fbf094f41af0274 TO UNIQ_5DA8BC7C41AF0274');
        $this->addSql('ALTER TABLE core_company_module RENAME INDEX uniq_31bb425db5b48b91 TO UNIQ_CAE7ABD2B5B48B91');
        $this->addSql('ALTER TABLE core_company_module RENAME INDEX idx_31bb425d9122a03f TO IDX_CAE7ABD29122A03F');
        $this->addSql('ALTER TABLE core_company_module RENAME INDEX idx_31bb425d16fe72e1 TO IDX_CAE7ABD216FE72E1');
        $this->addSql('ALTER TABLE core_company_module RENAME INDEX uniq_31bb425d9122a03f77153098 TO UNIQ_CAE7ABD29122A03F77153098');
        $this->addSql('ALTER TABLE core_company_module_log RENAME INDEX idx_dec288a72a1393c5 TO IDX_F1AAFC292A1393C5');
        $this->addSql('ALTER TABLE core_company_module_log RENAME INDEX idx_dec288a7699b6baf TO IDX_F1AAFC29699B6BAF');
        $this->addSql('ALTER TABLE core_company_status RENAME INDEX uniq_469f0169b5b48b91 TO UNIQ_BDC3E8E6B5B48B91');
        $this->addSql('ALTER TABLE core_company_status RENAME INDEX idx_469f01699122a03f TO IDX_BDC3E8E69122A03F');
        $this->addSql('ALTER TABLE core_file RENAME INDEX uniq_8c9f3610b5b48b91 TO UNIQ_BE7AF525B5B48B91');
        $this->addSql('ALTER TABLE core_file RENAME INDEX uniq_8c9f3610173b2587 TO UNIQ_BE7AF525173B2587');
        $this->addSql('ALTER TABLE core_file RENAME INDEX idx_8c9f361051b07d6d TO IDX_BE7AF52551B07D6D');
        $this->addSql('ALTER TABLE core_file_download RENAME INDEX idx_c94a0dedc7bb1f8a TO IDX_41EFE7B2C7BB1F8A');
        $this->addSql('ALTER TABLE core_file_download RENAME INDEX idx_c94a0ded699b6baf TO IDX_41EFE7B2699B6BAF');
        $this->addSql('ALTER TABLE core_file_version RENAME INDEX uniq_e47a6af8b5b48b91 TO UNIQ_49CAD320B5B48B91');
        $this->addSql('ALTER TABLE core_file_version RENAME INDEX idx_e47a6af87bf2a12 TO IDX_49CAD3207BF2A12');
        $this->addSql('ALTER TABLE core_file_version RENAME INDEX idx_e47a6af8699b6baf TO IDX_49CAD320699B6BAF');
        $this->addSql('ALTER TABLE core_file_version_signature RENAME INDEX uniq_e3bd6857b5b48b91 TO UNIQ_BB36B525B5B48B91');
        $this->addSql('ALTER TABLE core_file_version_signature RENAME INDEX idx_e3bd6857c7bb1f8a TO IDX_BB36B525C7BB1F8A');
        $this->addSql('ALTER TABLE core_file_version_signature RENAME INDEX idx_e3bd68572b0dc78f TO IDX_BB36B5252B0DC78F');
        $this->addSql('ALTER TABLE core_file_version_signature RENAME INDEX idx_e3bd6857699b6baf TO IDX_BB36B525699B6BAF');
        $this->addSql('ALTER TABLE core_legal_document RENAME INDEX uniq_72a4fdb7b5b48b91 TO UNIQ_89F81438B5B48B91');
        $this->addSql('ALTER TABLE core_staff RENAME INDEX uniq_426ef392b5b48b91 TO UNIQ_14EFD272B5B48B91');
        $this->addSql('ALTER TABLE core_staff RENAME INDEX idx_426ef3929122a03f TO IDX_14EFD2729122A03F');
        $this->addSql('ALTER TABLE core_staff RENAME INDEX idx_426ef392e173b1b8 TO IDX_14EFD272E173B1B8');
        $this->addSql('ALTER TABLE core_staff RENAME INDEX uniq_426ef39241af0274 TO UNIQ_14EFD27241AF0274');
        $this->addSql('ALTER TABLE core_staff RENAME INDEX uniq_426ef392e173b1b89122a03f TO UNIQ_14EFD272E173B1B89122A03F');
        $this->addSql('ALTER TABLE core_staff_market_segment RENAME INDEX idx_523d18f2d4d57cd TO IDX_A055377AD4D57CD');
        $this->addSql('ALTER TABLE core_staff_market_segment RENAME INDEX idx_523d18f2b5d73eb1 TO IDX_A055377AB5D73EB1');
        $this->addSql('ALTER TABLE core_staff_log RENAME INDEX idx_133f30c699b6baf TO IDX_4E53C328699B6BAF');
        $this->addSql('ALTER TABLE core_staff_status RENAME INDEX uniq_7e7dd7a7b5b48b91 TO UNIQ_D3CD6E7FB5B48B91');
        $this->addSql('ALTER TABLE core_staff_status RENAME INDEX idx_7e7dd7a7acebb2a2 TO IDX_D3CD6E7FACEBB2A2');
        $this->addSql('ALTER TABLE core_staff_status RENAME INDEX idx_7e7dd7a7699b6baf TO IDX_D3CD6E7F699B6BAF');
        $this->addSql('ALTER TABLE core_user_agent RENAME INDEX idx_c44967c5e173b1b8 TO IDX_F805E324E173B1B8');
        $this->addSql('ALTER TABLE core_user_agent RENAME INDEX idx_c44967c5e173b1b8d5438ed0111092bedad7193f5e78213 TO IDX_F805E324E173B1B8D5438ED0111092BEDAD7193F5E78213');
        $this->addSql('ALTER TABLE core_zz_versioned_clients RENAME INDEX idx_be25179da78d87a7 TO IDX_4C4D3815A78D87A7');
        $this->addSql('ALTER TABLE core_zz_versioned_clients RENAME INDEX idx_be25179df85e0677 TO IDX_4C4D3815F85E0677');
        $this->addSql('ALTER TABLE core_zz_versioned_clients RENAME INDEX idx_be25179d232d562b69684d7dbf1cd3c3 TO IDX_4C4D3815232D562B69684D7DBF1CD3C3');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE core_acceptations_legal_docs RENAME INDEX idx_4f817fff7f757bbc TO IDX_F1D2E4327F757BBC');
        $this->addSql('ALTER TABLE core_acceptations_legal_docs RENAME INDEX idx_4f817fffbd57fa7c TO IDX_F1D2E432BD57FA7C');
        $this->addSql('ALTER TABLE core_acceptations_legal_docs RENAME INDEX uniq_4f817fff7f757bbcbd57fa7c TO UNIQ_F1D2E4327F757BBCBD57FA7C');
        $this->addSql('ALTER TABLE core_acceptations_legal_docs RENAME INDEX uniq_4f817fffb5b48b91 TO UNIQ_F1D2E432B5B48B91');
        $this->addSql('ALTER TABLE core_client_successful_login RENAME INDEX idx_2ab3fcd861c3e712 TO IDX_94E0671561C3E712');
        $this->addSql('ALTER TABLE core_client_successful_login RENAME INDEX idx_2ab3fcd8e173b1b8 TO IDX_94E06715E173B1B8');
        $this->addSql('CREATE INDEX IDX_C82E74D1B862B8 ON core_clients (public_id)');
        $this->addSql('ALTER TABLE core_clients RENAME INDEX idx_12df9b47c808ba5a TO IDX_C82E74C808BA5A');
        $this->addSql('ALTER TABLE core_clients RENAME INDEX uniq_12df9b4741af0274 TO UNIQ_C82E7441AF0274');
        $this->addSql('ALTER TABLE core_clients RENAME INDEX uniq_12df9b47b5b48b91 TO UNIQ_C82E74B5B48B91');
        $this->addSql('ALTER TABLE core_clients RENAME INDEX uniq_12df9b47e7927c74 TO UNIQ_C82E74E7927C74');
        $this->addSql('DROP INDEX UNIQ_5DA8BC7CDA33CDFB ON core_company');
        $this->addSql('ALTER TABLE core_company RENAME INDEX idx_5da8bc7c91c00f TO IDX_4FBF094F91C00F');
        $this->addSql('ALTER TABLE core_company RENAME INDEX uniq_5da8bc7c17d2fe0d TO UNIQ_4FBF094F17D2FE0D');
        $this->addSql('ALTER TABLE core_company RENAME INDEX uniq_5da8bc7c41af0274 TO UNIQ_4FBF094F41AF0274');
        $this->addSql('ALTER TABLE core_company RENAME INDEX uniq_5da8bc7c8910b08d TO UNIQ_4FBF094F8910B08D');
        $this->addSql('ALTER TABLE core_company RENAME INDEX uniq_5da8bc7cb5b48b91 TO UNIQ_4FBF094FB5B48B91');
        $this->addSql('ALTER TABLE core_company RENAME INDEX uniq_5da8bc7cdb8bba08 TO UNIQ_4FBF094FDB8BBA08');
        $this->addSql('ALTER TABLE core_company RENAME INDEX uniq_5da8bc7cdd756216 TO UNIQ_4FBF094FDD756216');
        $this->addSql('ALTER TABLE core_company_module RENAME INDEX idx_cae7abd216fe72e1 TO IDX_31BB425D16FE72E1');
        $this->addSql('ALTER TABLE core_company_module RENAME INDEX idx_cae7abd29122a03f TO IDX_31BB425D9122A03F');
        $this->addSql('ALTER TABLE core_company_module RENAME INDEX uniq_cae7abd29122a03f77153098 TO UNIQ_31BB425D9122A03F77153098');
        $this->addSql('ALTER TABLE core_company_module RENAME INDEX uniq_cae7abd2b5b48b91 TO UNIQ_31BB425DB5B48B91');
        $this->addSql('ALTER TABLE core_company_module_log RENAME INDEX idx_f1aafc292a1393c5 TO IDX_DEC288A72A1393C5');
        $this->addSql('ALTER TABLE core_company_module_log RENAME INDEX idx_f1aafc29699b6baf TO IDX_DEC288A7699B6BAF');
        $this->addSql('ALTER TABLE core_company_status RENAME INDEX idx_bdc3e8e69122a03f TO IDX_469F01699122A03F');
        $this->addSql('ALTER TABLE core_company_status RENAME INDEX uniq_bdc3e8e6b5b48b91 TO UNIQ_469F0169B5B48B91');
        $this->addSql('ALTER TABLE core_file RENAME INDEX idx_be7af52551b07d6d TO IDX_8C9F361051B07D6D');
        $this->addSql('ALTER TABLE core_file RENAME INDEX uniq_be7af525173b2587 TO UNIQ_8C9F3610173B2587');
        $this->addSql('ALTER TABLE core_file RENAME INDEX uniq_be7af525b5b48b91 TO UNIQ_8C9F3610B5B48B91');
        $this->addSql('ALTER TABLE core_file_download RENAME INDEX idx_41efe7b2699b6baf TO IDX_C94A0DED699B6BAF');
        $this->addSql('ALTER TABLE core_file_download RENAME INDEX idx_41efe7b2c7bb1f8a TO IDX_C94A0DEDC7BB1F8A');
        $this->addSql('ALTER TABLE core_file_version RENAME INDEX idx_49cad320699b6baf TO IDX_E47A6AF8699B6BAF');
        $this->addSql('ALTER TABLE core_file_version RENAME INDEX idx_49cad3207bf2a12 TO IDX_E47A6AF87BF2A12');
        $this->addSql('ALTER TABLE core_file_version RENAME INDEX uniq_49cad320b5b48b91 TO UNIQ_E47A6AF8B5B48B91');
        $this->addSql('ALTER TABLE core_file_version_signature RENAME INDEX idx_bb36b5252b0dc78f TO IDX_E3BD68572B0DC78F');
        $this->addSql('ALTER TABLE core_file_version_signature RENAME INDEX idx_bb36b525699b6baf TO IDX_E3BD6857699B6BAF');
        $this->addSql('ALTER TABLE core_file_version_signature RENAME INDEX idx_bb36b525c7bb1f8a TO IDX_E3BD6857C7BB1F8A');
        $this->addSql('ALTER TABLE core_file_version_signature RENAME INDEX uniq_bb36b525b5b48b91 TO UNIQ_E3BD6857B5B48B91');
        $this->addSql('ALTER TABLE core_legal_document RENAME INDEX uniq_89f81438b5b48b91 TO UNIQ_72A4FDB7B5B48B91');
        $this->addSql('ALTER TABLE core_staff RENAME INDEX idx_14efd2729122a03f TO IDX_426EF3929122A03F');
        $this->addSql('ALTER TABLE core_staff RENAME INDEX idx_14efd272e173b1b8 TO IDX_426EF392E173B1B8');
        $this->addSql('ALTER TABLE core_staff RENAME INDEX uniq_14efd27241af0274 TO UNIQ_426EF39241AF0274');
        $this->addSql('ALTER TABLE core_staff RENAME INDEX uniq_14efd272b5b48b91 TO UNIQ_426EF392B5B48B91');
        $this->addSql('ALTER TABLE core_staff RENAME INDEX uniq_14efd272e173b1b89122a03f TO UNIQ_426EF392E173B1B89122A03F');
        $this->addSql('ALTER TABLE core_staff_log RENAME INDEX idx_4e53c328699b6baf TO IDX_133F30C699B6BAF');
        $this->addSql('ALTER TABLE core_staff_market_segment RENAME INDEX idx_a055377ab5d73eb1 TO IDX_523D18F2B5D73EB1');
        $this->addSql('ALTER TABLE core_staff_market_segment RENAME INDEX idx_a055377ad4d57cd TO IDX_523D18F2D4D57CD');
        $this->addSql('ALTER TABLE core_staff_status RENAME INDEX idx_d3cd6e7f699b6baf TO IDX_7E7DD7A7699B6BAF');
        $this->addSql('ALTER TABLE core_staff_status RENAME INDEX idx_d3cd6e7facebb2a2 TO IDX_7E7DD7A7ACEBB2A2');
        $this->addSql('ALTER TABLE core_staff_status RENAME INDEX uniq_d3cd6e7fb5b48b91 TO UNIQ_7E7DD7A7B5B48B91');
        $this->addSql('ALTER TABLE core_user_agent RENAME INDEX idx_f805e324e173b1b8 TO IDX_C44967C5E173B1B8');
        $this->addSql('ALTER TABLE core_user_agent RENAME INDEX idx_f805e324e173b1b8d5438ed0111092bedad7193f5e78213 TO IDX_C44967C5E173B1B8D5438ED0111092BEDAD7193F5E78213');
        $this->addSql('ALTER TABLE core_zz_versioned_clients RENAME INDEX idx_4c4d3815232d562b69684d7dbf1cd3c3 TO IDX_BE25179D232D562B69684D7DBF1CD3C3');
        $this->addSql('ALTER TABLE core_zz_versioned_clients RENAME INDEX idx_4c4d3815a78d87a7 TO IDX_BE25179DA78D87A7');
        $this->addSql('ALTER TABLE core_zz_versioned_clients RENAME INDEX idx_4c4d3815f85e0677 TO IDX_BE25179DF85E0677');
    }
}
