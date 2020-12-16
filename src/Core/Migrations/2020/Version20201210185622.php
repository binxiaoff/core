<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201210185622 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription() : string
    {
        return 'CALS-2713 Rename client to user';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE core_clients DROP FOREIGN KEY FK_C82E74B0D1B111');
        $this->addSql('ALTER TABLE core_acceptations_legal_docs DROP FOREIGN KEY FK_F1D2E432BD57FA7C');
        $this->addSql('ALTER TABLE core_client_status DROP FOREIGN KEY FK_2EA62EAFE173B1B8');
        $this->addSql('ALTER TABLE core_client_successful_login DROP FOREIGN KEY FK_94E06715E173B1B8');
        $this->addSql('ALTER TABLE core_staff DROP FOREIGN KEY FK_426EF392E173B1B8');
        $this->addSql('ALTER TABLE core_temporary_token DROP FOREIGN KEY FK_B7D82B15E173B1B8');
        $this->addSql('ALTER TABLE core_user_agent DROP FOREIGN KEY FK_C44967C5E173B1B8');
        $this->addSql('ALTER TABLE syndication_project DROP FOREIGN KEY FK_2FB3D0EEEE78DD55');
        $this->addSql('ALTER TABLE syndication_project_comment DROP FOREIGN KEY FK_26A5E09E173B1B8');
        $this->addSql('RENAME TABLE core_client_failed_login TO core_user_failed_login');
        $this->addSql('RENAME TABLE core_client_status TO core_user_status');
        $this->addSql('RENAME TABLE core_client_successful_login TO core_user_successful_login');
        $this->addSql('RENAME TABLE core_clients TO core_user');
        $this->addSql('RENAME TABLE core_zz_versioned_clients TO core_zz_versioned_user');
        $this->addSql('ALTER TABLE core_acceptations_legal_docs ADD CONSTRAINT FK_4F817FFFBD57FA7C FOREIGN KEY (accepted_by) REFERENCES core_user (id)');
        $this->addSql('DROP INDEX IDX_14EFD272E173B1B8 ON core_staff');
        $this->addSql('DROP INDEX UNIQ_14EFD272E173B1B89122A03F ON core_staff');
        $this->addSql('ALTER TABLE core_staff CHANGE id_client id_user INT NOT NULL');
        $this->addSql('ALTER TABLE core_staff ADD CONSTRAINT FK_14EFD2726B3CA4B FOREIGN KEY (id_user) REFERENCES core_user (id)');
        $this->addSql('CREATE INDEX IDX_14EFD2726B3CA4B ON core_staff (id_user)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_14EFD2726B3CA4B9122A03F ON core_staff (id_user, id_company)');
        $this->addSql('DROP INDEX fk_temporary_token_id_client ON core_temporary_token');
        $this->addSql('ALTER TABLE core_temporary_token CHANGE id_client id_user INT NOT NULL');
        $this->addSql('ALTER TABLE core_temporary_token ADD CONSTRAINT FK_CA24E94D6B3CA4B FOREIGN KEY (id_user) REFERENCES core_user (id)');
        $this->addSql('CREATE INDEX fk_temporary_token_id_user ON core_temporary_token (id_user)');
        $this->addSql('DROP INDEX IDX_F805E324E173B1B8 ON core_user_agent');
        $this->addSql('DROP INDEX IDX_F805E324E173B1B8D5438ED0111092BEDAD7193F5E78213 ON core_user_agent');
        $this->addSql('ALTER TABLE core_user_agent CHANGE id_client id_user INT NOT NULL');
        $this->addSql('ALTER TABLE core_user_agent ADD CONSTRAINT FK_F805E3246B3CA4B FOREIGN KEY (id_user) REFERENCES core_user (id)');
        $this->addSql('CREATE INDEX IDX_F805E3246B3CA4B ON core_user_agent (id_user)');
        $this->addSql('CREATE INDEX IDX_F805E3246B3CA4BD5438ED0111092BEDAD7193F5E78213 ON core_user_agent (id_user, browser_name, device_model, device_brand, device_type)');
        $this->addSql('ALTER TABLE syndication_interest_reply_version RENAME INDEX idx_cd6cfedfae73e249 TO IDX_67F999BAE73E249');
        $this->addSql('ALTER TABLE syndication_interest_reply_version RENAME INDEX idx_cd6cfedf699b6baf TO IDX_67F999B699B6BAF');
        $this->addSql('ALTER TABLE syndication_invitation_reply_version RENAME INDEX idx_ab14feddf263895d TO IDX_ECD74B34F263895D');
        $this->addSql('ALTER TABLE syndication_invitation_reply_version RENAME INDEX idx_ab14feddb99af4da TO IDX_ECD74B341BEAFC95');
        $this->addSql('ALTER TABLE syndication_invitation_reply_version RENAME INDEX idx_ab14fedd699b6baf TO IDX_ECD74B34699B6BAF');
        $this->addSql('DROP INDEX IDX_2FB3D0EEEE78DD55 ON syndication_project');
        $this->addSql('ALTER TABLE syndication_project CHANGE id_client_submitter id_user_submitter INT NOT NULL');
        $this->addSql('ALTER TABLE syndication_project ADD CONSTRAINT FK_7E9E0E6FDB546869 FOREIGN KEY (id_user_submitter) REFERENCES core_user (id)');
        $this->addSql('CREATE INDEX IDX_7E9E0E6FDB546869 ON syndication_project (id_user_submitter)');
        $this->addSql('ALTER TABLE syndication_project RENAME INDEX uniq_2fb3d0eeb5b48b91 TO UNIQ_7E9E0E6FB5B48B91');
        $this->addSql('ALTER TABLE syndication_project RENAME INDEX idx_2fb3d0ee24feba6c TO IDX_7E9E0E6F24FEBA6C');
        $this->addSql('ALTER TABLE syndication_project RENAME INDEX idx_2fb3d0ee2c71a0e3 TO IDX_7E9E0E6F2C71A0E3');
        $this->addSql('ALTER TABLE syndication_project RENAME INDEX uniq_2fb3d0ee61aa99f6 TO UNIQ_7E9E0E6F61AA99F6');
        $this->addSql('ALTER TABLE syndication_project RENAME INDEX uniq_2fb3d0ee1888280f TO UNIQ_7E9E0E6F1888280F');
        $this->addSql('ALTER TABLE syndication_project RENAME INDEX uniq_2fb3d0ee41af0274 TO UNIQ_7E9E0E6F41AF0274');
        $this->addSql('ALTER TABLE syndication_project_tag RENAME INDEX idx_91f26d60166d1f9c TO IDX_7F469F89166D1F9C');
        $this->addSql('ALTER TABLE syndication_project_tag RENAME INDEX idx_91f26d60bad26311 TO IDX_7F469F89BAD26311');
        $this->addSql('DROP INDEX IDX_26A5E09E173B1B8 ON syndication_project_comment');
        $this->addSql('ALTER TABLE syndication_project_comment CHANGE id_client id_user INT NOT NULL');
        $this->addSql('ALTER TABLE syndication_project_comment ADD CONSTRAINT FK_D84653C66B3CA4B FOREIGN KEY (id_user) REFERENCES core_user (id)');
        $this->addSql('CREATE INDEX IDX_D84653C66B3CA4B ON syndication_project_comment (id_user)');
        $this->addSql('ALTER TABLE syndication_project_comment RENAME INDEX idx_26a5e091bb9d5a2 TO IDX_D84653C61BB9D5A2');
        $this->addSql('ALTER TABLE syndication_project_comment RENAME INDEX idx_26a5e09f12e799e TO IDX_D84653C6F12E799E');
        $this->addSql('ALTER TABLE syndication_project_file RENAME INDEX uniq_b50efe08b5b48b91 TO UNIQ_6C361026B5B48B91');
        $this->addSql('ALTER TABLE syndication_project_file RENAME INDEX uniq_b50efe087bf2a12 TO UNIQ_6C3610267BF2A12');
        $this->addSql('ALTER TABLE syndication_project_file RENAME INDEX idx_b50efe08f12e799e TO IDX_6C361026F12E799E');
        $this->addSql('ALTER TABLE syndication_project_file RENAME INDEX idx_b50efe08699b6baf TO IDX_6C361026699B6BAF');
        $this->addSql('ALTER TABLE syndication_project_message RENAME INDEX uniq_20a33c1ab5b48b91 TO UNIQ_FA8F31D5B5B48B91');
        $this->addSql('ALTER TABLE syndication_project_message RENAME INDEX idx_20a33c1a157d332a TO IDX_FA8F31D5157D332A');
        $this->addSql('ALTER TABLE syndication_project_message RENAME INDEX idx_20a33c1a699b6baf TO IDX_FA8F31D5699B6BAF');
        $this->addSql('ALTER TABLE syndication_project_organizer RENAME INDEX idx_88e834a4f12e799e TO IDX_BA3B59B4F12E799E');
        $this->addSql('ALTER TABLE syndication_project_organizer RENAME INDEX idx_88e834a49122a03f TO IDX_BA3B59B49122A03F');
        $this->addSql('ALTER TABLE syndication_project_organizer RENAME INDEX idx_88e834a4699b6baf TO IDX_BA3B59B4699B6BAF');
        $this->addSql('ALTER TABLE syndication_project_organizer RENAME INDEX uniq_88e834a4f12e799e9122a03f TO UNIQ_BA3B59B4F12E799E9122A03F');
        $this->addSql('ALTER TABLE syndication_project_participation RENAME INDEX uniq_7fc47549b5b48b91 TO UNIQ_D10BDF9B5B48B91');
        $this->addSql('ALTER TABLE syndication_project_participation RENAME INDEX idx_7fc47549f12e799e TO IDX_D10BDF9F12E799E');
        $this->addSql('ALTER TABLE syndication_project_participation RENAME INDEX idx_7fc475499122a03f TO IDX_D10BDF99122A03F');
        $this->addSql('ALTER TABLE syndication_project_participation RENAME INDEX uniq_7fc4754941af0274 TO UNIQ_D10BDF941AF0274');
        $this->addSql('ALTER TABLE syndication_project_participation RENAME INDEX uniq_7fc475491888280f TO UNIQ_D10BDF91888280F');
        $this->addSql('ALTER TABLE syndication_project_participation RENAME INDEX idx_7fc47549699b6baf TO IDX_D10BDF9699B6BAF');
        $this->addSql('ALTER TABLE syndication_project_participation RENAME INDEX uniq_7fc47549f12e799e9122a03f TO UNIQ_D10BDF9F12E799E9122A03F');
        $this->addSql('ALTER TABLE syndication_project_participation_member RENAME INDEX uniq_2c624ff2b5b48b91 TO UNIQ_4CEF2D05B5B48B91');
        $this->addSql('ALTER TABLE syndication_project_participation_member RENAME INDEX idx_2c624ff2ae73e249 TO IDX_4CEF2D05AE73E249');
        $this->addSql('ALTER TABLE syndication_project_participation_member RENAME INDEX idx_2c624ff2acebb2a2 TO IDX_4CEF2D05ACEBB2A2');
        $this->addSql('ALTER TABLE syndication_project_participation_member RENAME INDEX idx_2c624ff2efc7ea74 TO IDX_4CEF2D05EFC7EA74');
        $this->addSql('ALTER TABLE syndication_project_participation_member RENAME INDEX idx_2c624ff2699b6baf TO IDX_4CEF2D05699B6BAF');
        $this->addSql('ALTER TABLE syndication_project_participation_member RENAME INDEX idx_2c624ff251b07d6d TO IDX_4CEF2D0551B07D6D');
        $this->addSql('ALTER TABLE syndication_project_participation_member RENAME INDEX uniq_2c624ff2acebb2a2ae73e249 TO UNIQ_4CEF2D05ACEBB2A2AE73E249');
        $this->addSql('ALTER TABLE syndication_project_participation_status RENAME INDEX uniq_2786d096b5b48b91 TO UNIQ_470BB261B5B48B91');
        $this->addSql('ALTER TABLE syndication_project_participation_status RENAME INDEX idx_2786d096ae73e249 TO IDX_470BB261AE73E249');
        $this->addSql('ALTER TABLE syndication_project_participation_status RENAME INDEX idx_2786d096699b6baf TO IDX_470BB261699B6BAF');
        $this->addSql('ALTER TABLE syndication_project_participation_status RENAME INDEX idx_2786d0967b00651cae73e249 TO IDX_470BB2617B00651CAE73E249');
        $this->addSql('ALTER TABLE syndication_project_participation_tranche RENAME INDEX uniq_6b56b4cbb5b48b91 TO UNIQ_48EF5E16B5B48B91');
        $this->addSql('ALTER TABLE syndication_project_participation_tranche RENAME INDEX idx_6b56b4cbb8faf130 TO IDX_48EF5E16B8FAF130');
        $this->addSql('ALTER TABLE syndication_project_participation_tranche RENAME INDEX idx_6b56b4cbae73e249 TO IDX_48EF5E16AE73E249');
        $this->addSql('ALTER TABLE syndication_project_participation_tranche RENAME INDEX idx_6b56b4cb699b6baf TO IDX_48EF5E16699B6BAF');
        $this->addSql('ALTER TABLE syndication_project_participation_tranche RENAME INDEX uniq_6b56b4cbb8faf130ae73e249 TO UNIQ_48EF5E16B8FAF130AE73E249');
        $this->addSql('ALTER TABLE syndication_tranche RENAME INDEX uniq_66675840b5b48b91 TO UNIQ_374A86C1B5B48B91');
        $this->addSql('ALTER TABLE syndication_tranche RENAME INDEX idx_66675840f12e799e TO IDX_374A86C1F12E799E');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project RENAME INDEX idx_915ee907a78d87a7 TO IDX_42F1DCC1A78D87A7');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project RENAME INDEX idx_915ee907f85e0677 TO IDX_42F1DCC1F85E0677');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project RENAME INDEX idx_915ee907232d562b69684d7dbf1cd3c3 TO IDX_42F1DCC1232D562B69684D7DBF1CD3C3');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_comment RENAME INDEX idx_a6233eeba78d87a7 TO IDX_C6AE5C1CA78D87A7');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_comment RENAME INDEX idx_a6233eebf85e0677 TO IDX_C6AE5C1CF85E0677');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_comment RENAME INDEX idx_a6233eeb232d562b69684d7dbf1cd3c3 TO IDX_C6AE5C1C232D562B69684D7DBF1CD3C3');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation RENAME INDEX idx_85175a86a78d87a7 TO IDX_3996F2CCA78D87A7');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation RENAME INDEX idx_85175a86f85e0677 TO IDX_3996F2CCF85E0677');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation RENAME INDEX idx_85175a86232d562b69684d7dbf1cd3c3 TO IDX_3996F2CC232D562B69684D7DBF1CD3C3');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation_tranche RENAME INDEX idx_670b8e58a78d87a7 TO IDX_ABC9EF1CA78D87A7');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation_tranche RENAME INDEX idx_670b8e58f85e0677 TO IDX_ABC9EF1CF85E0677');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation_tranche RENAME INDEX idx_670b8e58232d562b69684d7dbf1cd3c3 TO IDX_ABC9EF1C232D562B69684D7DBF1CD3C3');
        $this->addSql('ALTER TABLE syndication_zz_versioned_tranche RENAME INDEX idx_d88a61a9a78d87a7 TO IDX_B25546FA78D87A7');
        $this->addSql('ALTER TABLE syndication_zz_versioned_tranche RENAME INDEX idx_d88a61a9f85e0677 TO IDX_B25546FF85E0677');
        $this->addSql('ALTER TABLE syndication_zz_versioned_tranche RENAME INDEX idx_d88a61a9232d562b69684d7dbf1cd3c3 TO IDX_B25546F232D562B69684D7DBF1CD3C3');
        $this->addSql('ALTER TABLE core_user ADD CONSTRAINT FK_BF76157C41AF0274 FOREIGN KEY (id_current_status) REFERENCES core_user_status (id)');
        $this->addSql('ALTER TABLE core_user RENAME INDEX uniq_12df9b47e7927c74 TO UNIQ_BF76157CE7927C74');
        $this->addSql('ALTER TABLE core_user RENAME INDEX uniq_12df9b47b5b48b91 TO UNIQ_BF76157CB5B48B91');
        $this->addSql('ALTER TABLE core_user RENAME INDEX uniq_12df9b4741af0274 TO UNIQ_BF76157C41AF0274');
        $this->addSql('ALTER TABLE core_user RENAME INDEX idx_12df9b47c808ba5a TO IDX_BF76157CC808BA5A');
        $this->addSql('ALTER TABLE core_user_failed_login RENAME INDEX idx_client_failed_login_username TO idx_user_failed_login_username');
        $this->addSql('ALTER TABLE core_user_failed_login RENAME INDEX idx_client_failed_login_ip TO idx_user_failed_login_ip');
        $this->addSql('DROP INDEX idx_client_status_id_client ON core_user_status');
        $this->addSql('ALTER TABLE core_user_status CHANGE id_client id_user INT NOT NULL');
        $this->addSql('ALTER TABLE core_user_status ADD CONSTRAINT FK_C963E04B6B3CA4B FOREIGN KEY (id_user) REFERENCES core_user (id)');
        $this->addSql('CREATE INDEX idx_user_status_id_user ON core_user_status (id_user)');
        $this->addSql('ALTER TABLE core_user_status RENAME INDEX idx_client_status_status TO idx_user_status_status');
        $this->addSql('DROP INDEX IDX_2AB3FCD8E173B1B8 ON core_user_successful_login');
        $this->addSql('ALTER TABLE core_user_successful_login CHANGE id_client id_user INT NOT NULL');
        $this->addSql('ALTER TABLE core_user_successful_login ADD CONSTRAINT FK_2EADC30E6B3CA4B FOREIGN KEY (id_user) REFERENCES core_user (id)');
        $this->addSql('CREATE INDEX IDX_2EADC30E6B3CA4B ON core_user_successful_login (id_user)');
        $this->addSql('ALTER TABLE core_user_successful_login RENAME INDEX idx_2ab3fcd861c3e712 TO IDX_2EADC30E61C3E712');
        $this->addSql('ALTER TABLE core_user_successful_login RENAME INDEX idx_client_successful_login_ip TO idx_user_successful_login_ip');
        $this->addSql('ALTER TABLE core_user_successful_login RENAME INDEX idx_client_successful_login_added TO idx_user_successful_login_added');
        $this->addSql('ALTER TABLE core_zz_versioned_user RENAME INDEX idx_4c4d3815a78d87a7 TO IDX_F04DD111A78D87A7');
        $this->addSql('ALTER TABLE core_zz_versioned_user RENAME INDEX idx_4c4d3815f85e0677 TO IDX_F04DD111F85E0677');
        $this->addSql('ALTER TABLE core_zz_versioned_user RENAME INDEX idx_4c4d3815232d562b69684d7dbf1cd3c3 TO IDX_F04DD111232D562B69684D7DBF1CD3C3');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE core_zz_versioned_user RENAME INDEX idx_f04dd111232d562b69684d7dbf1cd3c3 TO IDX_4C4D3815232D562B69684D7DBF1CD3C3');
        $this->addSql('ALTER TABLE core_zz_versioned_user RENAME INDEX idx_f04dd111a78d87a7 TO IDX_4C4D3815A78D87A7');
        $this->addSql('ALTER TABLE core_zz_versioned_user RENAME INDEX idx_f04dd111f85e0677 TO IDX_4C4D3815F85E0677');
        $this->addSql('ALTER TABLE core_user DROP FOREIGN KEY FK_BF76157C41AF0274');
        $this->addSql('ALTER TABLE core_user RENAME INDEX idx_bf76157cc808ba5a TO IDX_12DF9B47C808BA5A');
        $this->addSql('ALTER TABLE core_user RENAME INDEX uniq_bf76157c41af0274 TO UNIQ_12DF9B4741AF0274');
        $this->addSql('ALTER TABLE core_user RENAME INDEX uniq_bf76157cb5b48b91 TO UNIQ_12DF9B47B5B48B91');
        $this->addSql('ALTER TABLE core_user RENAME INDEX uniq_bf76157ce7927c74 TO UNIQ_12DF9B47E7927C74');
        $this->addSql('ALTER TABLE core_user_failed_login RENAME INDEX idx_user_failed_login_ip TO idx_client_failed_login_ip');
        $this->addSql('ALTER TABLE core_user_failed_login RENAME INDEX idx_user_failed_login_username TO idx_client_failed_login_username');
        $this->addSql('ALTER TABLE core_user_status DROP FOREIGN KEY FK_C963E04B6B3CA4B');
        $this->addSql('DROP INDEX idx_user_status_id_user ON core_user_status');
        $this->addSql('ALTER TABLE core_user_status CHANGE id_user id_client INT NOT NULL');
        $this->addSql('CREATE INDEX idx_client_status_id_client ON core_user_status (id_client)');
        $this->addSql('ALTER TABLE core_user_status RENAME INDEX idx_user_status_status TO idx_client_status_status');
        $this->addSql('ALTER TABLE core_user_successful_login DROP FOREIGN KEY FK_2EADC30E6B3CA4B');
        $this->addSql('DROP INDEX IDX_2EADC30E6B3CA4B ON core_user_successful_login');
        $this->addSql('ALTER TABLE core_user_successful_login CHANGE id_user id_client INT NOT NULL');
        $this->addSql('CREATE INDEX IDX_2AB3FCD8E173B1B8 ON core_user_successful_login (id_client)');
        $this->addSql('ALTER TABLE core_user_successful_login RENAME INDEX idx_2eadc30e61c3e712 TO IDX_2AB3FCD861C3E712');
        $this->addSql('ALTER TABLE core_user_successful_login RENAME INDEX idx_user_successful_login_added TO idx_client_successful_login_added');
        $this->addSql('ALTER TABLE core_user_successful_login RENAME INDEX idx_user_successful_login_ip TO idx_client_successful_login_ip');

        $this->addSql('RENAME TABLE core_user_failed_login TO core_client_failed_login');
        $this->addSql('RENAME TABLE core_user_status TO core_client_status');
        $this->addSql('RENAME TABLE core_user_successful_login TO core_client_successful_login');
        $this->addSql('RENAME TABLE core_user TO core_clients');
        $this->addSql('RENAME TABLE core_zz_versioned_user TO core_zz_versioned_clients');
        $this->addSql('ALTER TABLE core_acceptations_legal_docs DROP FOREIGN KEY FK_4F817FFFBD57FA7C');
        $this->addSql('ALTER TABLE core_staff DROP FOREIGN KEY FK_14EFD2726B3CA4B');
        $this->addSql('ALTER TABLE core_temporary_token DROP FOREIGN KEY FK_CA24E94D6B3CA4B');
        $this->addSql('ALTER TABLE syndication_project DROP FOREIGN KEY FK_7E9E0E6FDB546869');
        $this->addSql('ALTER TABLE syndication_project_comment DROP FOREIGN KEY FK_D84653C66B3CA4B');
        $this->addSql('ALTER TABLE core_acceptations_legal_docs ADD CONSTRAINT FK_F1D2E432BD57FA7C FOREIGN KEY (accepted_by) REFERENCES core_clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP INDEX IDX_14EFD2726B3CA4B ON core_staff');
        $this->addSql('DROP INDEX UNIQ_14EFD2726B3CA4B9122A03F ON core_staff');
        $this->addSql('ALTER TABLE core_staff CHANGE id_user id_client INT NOT NULL');
        $this->addSql('ALTER TABLE core_staff ADD CONSTRAINT FK_426EF392E173B1B8 FOREIGN KEY (id_client) REFERENCES core_clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_14EFD272E173B1B8 ON core_staff (id_client)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_14EFD272E173B1B89122A03F ON core_staff (id_client, id_company)');
        $this->addSql('DROP INDEX fk_temporary_token_id_user ON core_temporary_token');
        $this->addSql('ALTER TABLE core_temporary_token CHANGE id_user id_client INT NOT NULL');
        $this->addSql('ALTER TABLE core_temporary_token ADD CONSTRAINT FK_B7D82B15E173B1B8 FOREIGN KEY (id_client) REFERENCES core_clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX fk_temporary_token_id_client ON core_temporary_token (id_client)');
        $this->addSql('DROP INDEX IDX_F805E3246B3CA4B ON core_user_agent');
        $this->addSql('DROP INDEX IDX_F805E3246B3CA4BD5438ED0111092BEDAD7193F5E78213 ON core_user_agent');
        $this->addSql('ALTER TABLE core_user_agent CHANGE id_user id_client INT NOT NULL');
        $this->addSql('ALTER TABLE core_user_agent ADD CONSTRAINT FK_C44967C5E173B1B8 FOREIGN KEY (id_client) REFERENCES core_clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_F805E324E173B1B8 ON core_user_agent (id_client)');
        $this->addSql('CREATE INDEX IDX_F805E324E173B1B8D5438ED0111092BEDAD7193F5E78213 ON core_user_agent (id_client, browser_name, device_model, device_brand, device_type)');
        $this->addSql('ALTER TABLE syndication_interest_reply_version RENAME INDEX idx_67f999b699b6baf TO IDX_CD6CFEDF699B6BAF');
        $this->addSql('ALTER TABLE syndication_interest_reply_version RENAME INDEX idx_67f999bae73e249 TO IDX_CD6CFEDFAE73E249');
        $this->addSql('ALTER TABLE syndication_invitation_reply_version RENAME INDEX idx_ecd74b34699b6baf TO IDX_AB14FEDD699B6BAF');
        $this->addSql('ALTER TABLE syndication_invitation_reply_version RENAME INDEX idx_ecd74b341beafc95 TO IDX_AB14FEDDB99AF4DA');
        $this->addSql('ALTER TABLE syndication_invitation_reply_version RENAME INDEX idx_ecd74b34f263895d TO IDX_AB14FEDDF263895D');
        $this->addSql('DROP INDEX IDX_7E9E0E6FDB546869 ON syndication_project');
        $this->addSql('ALTER TABLE syndication_project CHANGE id_user_submitter id_client_submitter INT NOT NULL');
        $this->addSql('ALTER TABLE syndication_project ADD CONSTRAINT FK_2FB3D0EEEE78DD55 FOREIGN KEY (id_client_submitter) REFERENCES core_clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_2FB3D0EEEE78DD55 ON syndication_project (id_client_submitter)');
        $this->addSql('ALTER TABLE syndication_project RENAME INDEX idx_7e9e0e6f24feba6c TO IDX_2FB3D0EE24FEBA6C');
        $this->addSql('ALTER TABLE syndication_project RENAME INDEX idx_7e9e0e6f2c71a0e3 TO IDX_2FB3D0EE2C71A0E3');
        $this->addSql('ALTER TABLE syndication_project RENAME INDEX uniq_7e9e0e6f1888280f TO UNIQ_2FB3D0EE1888280F');
        $this->addSql('ALTER TABLE syndication_project RENAME INDEX uniq_7e9e0e6f41af0274 TO UNIQ_2FB3D0EE41AF0274');
        $this->addSql('ALTER TABLE syndication_project RENAME INDEX uniq_7e9e0e6f61aa99f6 TO UNIQ_2FB3D0EE61AA99F6');
        $this->addSql('ALTER TABLE syndication_project RENAME INDEX uniq_7e9e0e6fb5b48b91 TO UNIQ_2FB3D0EEB5B48B91');
        $this->addSql('DROP INDEX IDX_D84653C66B3CA4B ON syndication_project_comment');
        $this->addSql('ALTER TABLE syndication_project_comment CHANGE id_user id_client INT NOT NULL');
        $this->addSql('ALTER TABLE syndication_project_comment ADD CONSTRAINT FK_26A5E09E173B1B8 FOREIGN KEY (id_client) REFERENCES core_clients (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_26A5E09E173B1B8 ON syndication_project_comment (id_client)');
        $this->addSql('ALTER TABLE syndication_project_comment RENAME INDEX idx_d84653c61bb9d5a2 TO IDX_26A5E091BB9D5A2');
        $this->addSql('ALTER TABLE syndication_project_comment RENAME INDEX idx_d84653c6f12e799e TO IDX_26A5E09F12E799E');
        $this->addSql('ALTER TABLE syndication_project_file RENAME INDEX idx_6c361026699b6baf TO IDX_B50EFE08699B6BAF');
        $this->addSql('ALTER TABLE syndication_project_file RENAME INDEX idx_6c361026f12e799e TO IDX_B50EFE08F12E799E');
        $this->addSql('ALTER TABLE syndication_project_file RENAME INDEX uniq_6c3610267bf2a12 TO UNIQ_B50EFE087BF2A12');
        $this->addSql('ALTER TABLE syndication_project_file RENAME INDEX uniq_6c361026b5b48b91 TO UNIQ_B50EFE08B5B48B91');
        $this->addSql('ALTER TABLE syndication_project_message RENAME INDEX idx_fa8f31d5157d332a TO IDX_20A33C1A157D332A');
        $this->addSql('ALTER TABLE syndication_project_message RENAME INDEX idx_fa8f31d5699b6baf TO IDX_20A33C1A699B6BAF');
        $this->addSql('ALTER TABLE syndication_project_message RENAME INDEX uniq_fa8f31d5b5b48b91 TO UNIQ_20A33C1AB5B48B91');
        $this->addSql('ALTER TABLE syndication_project_organizer RENAME INDEX idx_ba3b59b4699b6baf TO IDX_88E834A4699B6BAF');
        $this->addSql('ALTER TABLE syndication_project_organizer RENAME INDEX idx_ba3b59b49122a03f TO IDX_88E834A49122A03F');
        $this->addSql('ALTER TABLE syndication_project_organizer RENAME INDEX idx_ba3b59b4f12e799e TO IDX_88E834A4F12E799E');
        $this->addSql('ALTER TABLE syndication_project_organizer RENAME INDEX uniq_ba3b59b4f12e799e9122a03f TO UNIQ_88E834A4F12E799E9122A03F');
        $this->addSql('ALTER TABLE syndication_project_participation RENAME INDEX idx_d10bdf9699b6baf TO IDX_7FC47549699B6BAF');
        $this->addSql('ALTER TABLE syndication_project_participation RENAME INDEX idx_d10bdf99122a03f TO IDX_7FC475499122A03F');
        $this->addSql('ALTER TABLE syndication_project_participation RENAME INDEX idx_d10bdf9f12e799e TO IDX_7FC47549F12E799E');
        $this->addSql('ALTER TABLE syndication_project_participation RENAME INDEX uniq_d10bdf91888280f TO UNIQ_7FC475491888280F');
        $this->addSql('ALTER TABLE syndication_project_participation RENAME INDEX uniq_d10bdf941af0274 TO UNIQ_7FC4754941AF0274');
        $this->addSql('ALTER TABLE syndication_project_participation RENAME INDEX uniq_d10bdf9b5b48b91 TO UNIQ_7FC47549B5B48B91');
        $this->addSql('ALTER TABLE syndication_project_participation RENAME INDEX uniq_d10bdf9f12e799e9122a03f TO UNIQ_7FC47549F12E799E9122A03F');
        $this->addSql('ALTER TABLE syndication_project_participation_member RENAME INDEX idx_4cef2d0551b07d6d TO IDX_2C624FF251B07D6D');
        $this->addSql('ALTER TABLE syndication_project_participation_member RENAME INDEX idx_4cef2d05699b6baf TO IDX_2C624FF2699B6BAF');
        $this->addSql('ALTER TABLE syndication_project_participation_member RENAME INDEX idx_4cef2d05acebb2a2 TO IDX_2C624FF2ACEBB2A2');
        $this->addSql('ALTER TABLE syndication_project_participation_member RENAME INDEX idx_4cef2d05ae73e249 TO IDX_2C624FF2AE73E249');
        $this->addSql('ALTER TABLE syndication_project_participation_member RENAME INDEX idx_4cef2d05efc7ea74 TO IDX_2C624FF2EFC7EA74');
        $this->addSql('ALTER TABLE syndication_project_participation_member RENAME INDEX uniq_4cef2d05acebb2a2ae73e249 TO UNIQ_2C624FF2ACEBB2A2AE73E249');
        $this->addSql('ALTER TABLE syndication_project_participation_member RENAME INDEX uniq_4cef2d05b5b48b91 TO UNIQ_2C624FF2B5B48B91');
        $this->addSql('ALTER TABLE syndication_project_participation_status RENAME INDEX idx_470bb261699b6baf TO IDX_2786D096699B6BAF');
        $this->addSql('ALTER TABLE syndication_project_participation_status RENAME INDEX idx_470bb2617b00651cae73e249 TO IDX_2786D0967B00651CAE73E249');
        $this->addSql('ALTER TABLE syndication_project_participation_status RENAME INDEX idx_470bb261ae73e249 TO IDX_2786D096AE73E249');
        $this->addSql('ALTER TABLE syndication_project_participation_status RENAME INDEX uniq_470bb261b5b48b91 TO UNIQ_2786D096B5B48B91');
        $this->addSql('ALTER TABLE syndication_project_participation_tranche RENAME INDEX idx_48ef5e16699b6baf TO IDX_6B56B4CB699B6BAF');
        $this->addSql('ALTER TABLE syndication_project_participation_tranche RENAME INDEX idx_48ef5e16ae73e249 TO IDX_6B56B4CBAE73E249');
        $this->addSql('ALTER TABLE syndication_project_participation_tranche RENAME INDEX idx_48ef5e16b8faf130 TO IDX_6B56B4CBB8FAF130');
        $this->addSql('ALTER TABLE syndication_project_participation_tranche RENAME INDEX uniq_48ef5e16b5b48b91 TO UNIQ_6B56B4CBB5B48B91');
        $this->addSql('ALTER TABLE syndication_project_participation_tranche RENAME INDEX uniq_48ef5e16b8faf130ae73e249 TO UNIQ_6B56B4CBB8FAF130AE73E249');
        $this->addSql('ALTER TABLE syndication_project_tag RENAME INDEX idx_7f469f89166d1f9c TO IDX_91F26D60166D1F9C');
        $this->addSql('ALTER TABLE syndication_project_tag RENAME INDEX idx_7f469f89bad26311 TO IDX_91F26D60BAD26311');
        $this->addSql('ALTER TABLE syndication_tranche RENAME INDEX idx_374a86c1f12e799e TO IDX_66675840F12E799E');
        $this->addSql('ALTER TABLE syndication_tranche RENAME INDEX uniq_374a86c1b5b48b91 TO UNIQ_66675840B5B48B91');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project RENAME INDEX idx_42f1dcc1232d562b69684d7dbf1cd3c3 TO IDX_915EE907232D562B69684D7DBF1CD3C3');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project RENAME INDEX idx_42f1dcc1a78d87a7 TO IDX_915EE907A78D87A7');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project RENAME INDEX idx_42f1dcc1f85e0677 TO IDX_915EE907F85E0677');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_comment RENAME INDEX idx_c6ae5c1c232d562b69684d7dbf1cd3c3 TO IDX_A6233EEB232D562B69684D7DBF1CD3C3');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_comment RENAME INDEX idx_c6ae5c1ca78d87a7 TO IDX_A6233EEBA78D87A7');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_comment RENAME INDEX idx_c6ae5c1cf85e0677 TO IDX_A6233EEBF85E0677');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation RENAME INDEX idx_3996f2cc232d562b69684d7dbf1cd3c3 TO IDX_85175A86232D562B69684D7DBF1CD3C3');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation RENAME INDEX idx_3996f2cca78d87a7 TO IDX_85175A86A78D87A7');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation RENAME INDEX idx_3996f2ccf85e0677 TO IDX_85175A86F85E0677');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation_tranche RENAME INDEX idx_abc9ef1c232d562b69684d7dbf1cd3c3 TO IDX_670B8E58232D562B69684D7DBF1CD3C3');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation_tranche RENAME INDEX idx_abc9ef1ca78d87a7 TO IDX_670B8E58A78D87A7');
        $this->addSql('ALTER TABLE syndication_zz_versioned_project_participation_tranche RENAME INDEX idx_abc9ef1cf85e0677 TO IDX_670B8E58F85E0677');
        $this->addSql('ALTER TABLE syndication_zz_versioned_tranche RENAME INDEX idx_b25546f232d562b69684d7dbf1cd3c3 TO IDX_D88A61A9232D562B69684D7DBF1CD3C3');
        $this->addSql('ALTER TABLE syndication_zz_versioned_tranche RENAME INDEX idx_b25546fa78d87a7 TO IDX_D88A61A9A78D87A7');
        $this->addSql('ALTER TABLE syndication_zz_versioned_tranche RENAME INDEX idx_b25546ff85e0677 TO IDX_D88A61A9F85E0677');
    }
}
