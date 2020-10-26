<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201026174150 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-2687 Migrate email management to Mailjet';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mail_template DROP FOREIGN KEY FK_4AB7DECBC406B0BE');
        $this->addSql('ALTER TABLE mail_template DROP FOREIGN KEY FK_4AB7DECB48451D2C');
        $this->addSql('ALTER TABLE mail_template DROP FOREIGN KEY FK_4AB7DECB1C0DDE0F');
        $this->addSql('ALTER TABLE mail_queue DROP FOREIGN KEY FK_4B3EDD0CF49F0FAE');
        $this->addSql('DROP TABLE mail_footer');
        $this->addSql('DROP TABLE mail_header');
        $this->addSql('DROP TABLE mail_layout');
        $this->addSql('DROP TABLE mail_queue');
        $this->addSql('DROP TABLE mail_template');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE mail_footer (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, locale VARCHAR(5) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, content LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, archived DATETIME DEFAULT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE mail_header (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, locale VARCHAR(5) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, content LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, archived DATETIME DEFAULT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE mail_layout (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, locale VARCHAR(5) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, content LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, archived DATETIME DEFAULT NULL, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE mail_queue (id_queue INT AUTO_INCREMENT NOT NULL, id_mail_template INT NOT NULL, serialized_variables MEDIUMTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, attachments TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, recipient VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, reply_to VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, id_client INT DEFAULT NULL, id_message_mailjet BIGINT DEFAULT NULL, error_mailjet VARCHAR(256) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, status SMALLINT NOT NULL, to_send_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX id_client (id_client), INDEX id_message_mailjet (id_message_mailjet), INDEX IDX_4B3EDD0CF49F0FAE (id_mail_template), INDEX idx_mail_queue_sent_at (sent_at), INDEX recipient (recipient, id_mail_template), INDEX status (status), PRIMARY KEY(id_queue)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE mail_template (id INT AUTO_INCREMENT NOT NULL, id_header INT DEFAULT NULL, id_footer INT DEFAULT NULL, id_layout INT DEFAULT NULL, name VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, locale VARCHAR(5) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, content LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, archived DATETIME DEFAULT NULL, subject VARCHAR(191) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, sender_name VARCHAR(191) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, sender_email VARCHAR(191) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', added DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4AB7DECB1C0DDE0F (id_layout), INDEX IDX_4AB7DECB48451D2C (id_header), INDEX IDX_4AB7DECBC406B0BE (id_footer), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE mail_queue ADD CONSTRAINT FK_4B3EDD0CF49F0FAE FOREIGN KEY (id_mail_template) REFERENCES mail_template (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE mail_template ADD CONSTRAINT FK_4AB7DECB1C0DDE0F FOREIGN KEY (id_layout) REFERENCES mail_layout (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE mail_template ADD CONSTRAINT FK_4AB7DECB48451D2C FOREIGN KEY (id_header) REFERENCES mail_header (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE mail_template ADD CONSTRAINT FK_4AB7DECBC406B0BE FOREIGN KEY (id_footer) REFERENCES mail_footer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql(<<<SQL
INSERT INTO mail_footer (id, name, locale, content, archived, updated, added) VALUES (5, 'footer', 'fr_FR', '  <mj-column>
      <mj-text font-weight="100" font-size="11px" line-height="1.5" align="center" padding-top="40px" color="#3F2865">
          Copyright © 2020 KLS, tous droits réservés.<br/>
          Où nous trouver : 50 rue la Boétie, 75008 Paris, France<br/>
      </mj-text>
  </mj-column>', null, null, '2020-10-20 18:08:43');
SQL
        );
        $this->addSql(<<<SQL
INSERT INTO mail_header (id, name, locale, content, archived, updated, added) VALUES (5, 'header', 'fr_FR', '  <mj-column><mj-image align="left" width="60px" src="{{ url("front_image", {imageFileName: "emails/logo.png"}) }}"/></mj-column>
  <mj-column>
      <mj-text align="right" color="#ffffff" font-size="10px" font-family="Arial" line-height="50px">
          FINANCER MIEUX, FINANCER ENSEMBLE
      </mj-text>
  </mj-column>', null, null, '2020-10-20 18:08:43');
SQL
        );
        $this->addSql(<<<SQL
INSERT INTO mail_layout (id, name, locale, content, archived, updated, added) VALUES (9, 'layout', 'fr_FR', '      <mjml>
        <mj-head>
            <mj-style>
                a { color: #3F2865; font-weight: 600; text-decoration: none; }
            </mj-style>
        </mj-head>
        <mj-body>
            <mj-section background-color="#3F2865" padding="0">
                {% block header %}{% endblock %}
            </mj-section>
            <mj-section padding="0">
                <mj-column width="560px" background-color="#F5F4F7" padding="0 40px 30px">
                    {% block body %}{% endblock %}
                </mj-column>
            </mj-section>
            <mj-section>
                {% block footer %}{% endblock %}
            </mj-section>
        </mj-body>
    </mjml>', null, null, '2020-10-20 18:08:43');
SQL
        );
        $this->addSql(<<<SQL
INSERT INTO mail_layout (id, name, locale, content, archived, updated, added) VALUES (10, 'internal', 'fr_FR', '      <mjml>
        <mj-head>
            <mj-style>
                a { color: #3F2865; font-weight: 600; text-decoration: none; }
            </mj-style>
        </mj-head>
        <mj-body>
            <mj-section background-color="#3F2865" padding="0">
                {% block header %}{% endblock %}
            </mj-section>
            <mj-section padding="0">
                <mj-column width="560px" background-color="#F5F4F7" padding="0 40px 30px">
                    {% block body %}{% endblock %}
                </mj-column>
            </mj-section>
            <mj-section>
                {% block footer %}{% endblock %}
            </mj-section>
        </mj-body>
    </mjml>', null, null, '2020-10-20 18:08:43');
SQL
        );
        $this->addSql(<<<SQL
INSERT INTO mail_template (id, id_header, id_footer, id_layout, name, locale, content, archived, subject, sender_name, sender_email, updated, added) VALUES (49, 5, 5, 9, 'staff-client-initialisation', 'fr_FR', '{% set inscriptionFinalisationUrl = url("front_initialAccount", {temporaryTokenPublicId: temporaryToken.token, clientPublicId: client.publicId }) %}
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/fireworks.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour,</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
   Votre compte, pour {{ staff.company.displayName }} {{ staff.roles ? "en tant que " ~ staff.roles|join(", ") }} {{ staff.marketSegments ? "sur le(s) marché(s) " ~ staff.marketSegments|join(", ") }} sur la plateforme KLS, a été créé.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Nous vous invitons à aller compléter votre profil directement sur <a href="{{ inscriptionFinalisationUrl }}">{{ "kls-platform.com" }}</a>.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px" href="{{ inscriptionFinalisationUrl }}">
    Compléter le profil
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions,
    l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> ou via le live-chat disponible sur la plateforme.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>', null, 'KLS - Initialisation de votre compte', 'KLS', 'support@kls-platform.com', null, '2020-10-20 18:08:43');
SQL
        );
        $this->addSql(<<<SQL
INSERT INTO mail_template (id, id_header, id_footer, id_layout, name, locale, content, archived, subject, sender_name, sender_email, updated, added) VALUES (50, 5, 5, 9, 'client-password-request', 'fr_FR', '<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/reload.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour {{ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Vous avez demandé à réinitialiser votre mot de passe sur la plateforme KLS.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour cela, cliquez sur le lien ci-dessous. La durée de validité de ce lien est de 24h. Au-delà, merci de bien vouloir reformuler votre demande.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px"
           href="{{ url("front_resetPassword", {temporaryTokenPublicId: temporaryToken.token, clientPublicId: client.publicId}) }}">
    Réinitialiser le mot de passe
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Si vous n’avez pas demandé à réinitialiser votre mot de passe ou pour toute question, merci de contacter le support client KLS (<a href="mailto:support@kls-platform.com">support@kls-platform.com</a>)
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>', null, 'KLS – votre nouveau mot de passe', 'KLS', 'support@kls-platform.com', null, '2020-10-20 18:08:43');
SQL
        );
        $this->addSql(<<<SQL
INSERT INTO mail_template (id, id_header, id_footer, id_layout, name, locale, content, archived, subject, sender_name, sender_email, updated, added) VALUES (51, 5, 5, 9, 'publication-prospect-company', 'fr_FR', '<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à marquer votre intérêt sur la participation de votre établissement, {{ projectParticipation.participant.displayName }}, au financement du dossier {{ project.title }} – {{ project.riskGroupName }}, sur la plateforme KLS, l’outil d’aide à la syndication, accessible sur <a href="{{ url("front_home") }}">www.kls-platform.com</a>.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Votre Établissement n’étant pas encore adhérent à la plateforme, nous vous invitons à prendre contact auprès de <a href="mailto:cecile.joly@ca-lendingservices.com">cecile.joly@ca-lendingservices.com</a> ou de <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> afin de pouvoir rapidement formaliser votre adhésion et avoir accès au dossier.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>', null, 'KLS - vous avez reçu une demande de marque d’intérêt !', 'KLS', 'support@kls-platform.com', null, '2020-10-20 18:08:43');
SQL
        );
        $this->addSql(<<<SQL
INSERT INTO mail_template (id, id_header, id_footer, id_layout, name, locale, content, archived, subject, sender_name, sender_email, updated, added) VALUES (52, 5, 5, 9, 'publication-uninitialized-user', 'fr_FR', '<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à marquer votre intérêt sur la participation de votre établissement, {{ projectParticipation.participant.displayName }}, au financement du dossier {{ project.title }} – {{ project.riskGroupName }}.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Votre compte n’étant pas encore créé sur la plateforme d’aide à la syndication, KLS.
    Nous vous invitons à créer votre compte dès maintenant pour accéder au dossier.
    Votre demande d’habilitation sera transmise à votre responsable pour qu’il valide votre accès.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px" href="{{ url("front_initialAccount", {temporaryTokenPublicId: temporaryToken.token, clientPublicId: client.publicId }) }}">
    Créer mon compte
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions, l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> ou via le live-chat disponible sur la plateforme.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>', null, 'KLS - vous avez reçu une demande de marque d’intérêt !', 'KLS', 'support@kls-platform.com', null, '2020-10-20 18:08:43');
SQL
        );
        $this->addSql(<<<SQL
INSERT INTO mail_template (id, id_header, id_footer, id_layout, name, locale, content, archived, subject, sender_name, sender_email, updated, added) VALUES (53, 5, 5, 9, 'publication', 'fr_FR', '<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à marquer votre intérêt sur la participation de votre établissement, {{ projectParticipation.participant.displayName }}, au financement du dossier {{ project.title }} – {{ project.riskGroupName }}.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px"
href="{{ url("front_viewParticipation", {projectParticipationPublicId: projectParticipation.publicId}) }}">
    Consulter l’invitation
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions, l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> ou via le live-chat disponible sur la plateforme.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>', null, 'KLS - vous avez reçu une demande de marque d’intérêt !', 'KLS', 'support@kls-platform.com', null, '2020-10-20 18:08:43');
SQL
        );
        $this->addSql(<<<SQL
INSERT INTO mail_template (id, id_header, id_footer, id_layout, name, locale, content, archived, subject, sender_name, sender_email, updated, added) VALUES (54, 5, 5, 9, 'syndication-prospect-company', 'fr_FR', '<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à participer sur la participation de votre établissement, {{ projectParticipation.participant.displayName }}, au financement du dossier {{ project.title }} – {{ project.riskGroupName }}, sur la plateforme KLS, l’outil d’aide à la syndication, accessible sur <a href="{{ url("front_home") }}">www.kls-platform.com</a>.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Votre Établissement n’étant pas encore adhérent à la plateforme, nous vous invitons à prendre contact auprès de <a href="mailto:cecile.joly@ca-lendingservices.com">cecile.joly@ca-lendingservices.com</a> ou de <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> afin de pouvoir rapidement formaliser votre adhésion et avoir accès au dossier.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>', null, 'KLS - vous avez reçu une invitation !', 'KLS', 'support@kls-platform.com', null, '2020-10-20 18:08:43');
SQL
        );
        $this->addSql(<<<SQL
INSERT INTO mail_template (id, id_header, id_footer, id_layout, name, locale, content, archived, subject, sender_name, sender_email, updated, added) VALUES (55, 5, 5, 9, 'syndication-uninitialized-user', 'fr_FR', '<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à participer sur la participation de votre établissement, {{ projectParticipation.participant.displayName }}, au financement du dossier {{ project.title }} – {{ project.riskGroupName }}.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Votre compte n’étant pas encore créé sur la plateforme d’aide à la syndication, KLS.
    Nous vous invitons à créer votre compte dès maintenant pour accéder au dossier.
    Votre demande d’habilitation sera transmise à votre responsable pour qu’il valide votre accès.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px" href="{{ url("front_initialAccount", {temporaryTokenPublicId: temporaryToken.token, clientPublicId: client.publicId }) }}">
    Créer mon compte
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions, l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> ou via le live-chat disponible sur la plateforme.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>', null, 'KLS - vous avez reçu une invitation !', 'KLS', 'support@kls-platform.com', null, '2020-10-20 18:08:43');
SQL
        );
        $this->addSql(<<<SQL
INSERT INTO mail_template (id, id_header, id_footer, id_layout, name, locale, content, archived, subject, sender_name, sender_email, updated, added) VALUES (56, 5, 5, 9, 'syndication', 'fr_FR', '<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à participer sur la participation de votre établissement, {{ projectParticipation.participant.displayName }}, au financement du dossier {{ project.title }} – {{ project.riskGroupName }}.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px" href="{{ url("front_viewParticipation", {projectParticipationPublicId : projectParticipation.publicId}) }}">
    Consultez l’invitation
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions, l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> ou via le live-chat disponible sur la plateforme.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>', null, 'KLS - vous avez reçu une invitation !', 'KLS', 'support@kls-platform.com', null, '2020-10-20 18:08:43');
SQL
        );
        $this->addSql(<<<SQL
INSERT INTO mail_template (id, id_header, id_footer, id_layout, name, locale, content, archived, subject, sender_name, sender_email, updated, added) VALUES (57, 5, 5, 9, 'project-file-uploaded', 'fr_FR', '<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/attachment-uploaded.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour {{ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ project.arranger }} vient de charger un nouveau document sur le dossier {{ project.title }} – {{ project.riskGroupName }} dans la plateforme KLS.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px" href="{{ url("front_viewParticipation", {projectParticipationPublicId: projectParticipation.publicId}) }}">
    Consulter sur KLS
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions, l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a>) ou via le live-chat disponible sur la plateforme.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>', null, 'KLS – Nouveau document sur le dossier {{ project.title }} – {{ project.riskGroupName }}', 'KLS', 'support@kls-platform.com', null, '2020-10-20 18:08:43');
SQL
        );
        $this->addSql(<<<SQL
INSERT INTO mail_template (id, id_header, id_footer, id_layout, name, locale, content, archived, subject, sender_name, sender_email, updated, added) VALUES (58, 5, 5, 10, 'log', 'fr_FR', '<mj-column>
    <mj-text><h1>{{ message }}</h1></mj-text>
    {% if datetime is defined %}
    <mj-text color="gray">{{ datetime|date("c")}}</mj-text>
    {% endif %}
    {% if context is defined and context is not empty %}
    <mj-text><h2>Context</h2></mj-text>
      {% for key, value in context %}
        <mj-text><strong>{{ key }}</strong></mj-text>
        <mj-text><pre>{{ value }}</pre></mj-text>
        {% if loop.last is same as(false) %}
        <mj-divider border-width="1px" border-style="dashed" border-color="lightgrey"/>
        {% endif %}
      {% endfor %}
    {% endif %}
    <mj-divider border-width="3px" border-style="solid" border-color="black" />
    {% if extra is defined and extra is not empty %}
    <mj-text><h2>Extra</h2></mj-text>
      {% for key, value in extra %}
        <mj-text><strong>{{ key }}</strong></mj-text>
        <mj-text><pre>{{ value }}</pre></mj-text>
        {% if loop.last is same as(false) %}
        <mj-divider border-width="1px" border-style="dashed" border-color="lightgrey"/>
        {% endif %}
      {% endfor %}
    {% endif %}
  </mj-column>', null, '{{ level_name }} on KLS {{ environment|default(false) ? ''in '' ~ environment }}', 'KLS', 'support@kls-platform.com', null, '2020-10-20 18:08:43');
SQL
        );
        $this->addSql(<<<SQL
INSERT INTO mail_template (id, id_header, id_footer, id_layout, name, locale, content, archived, subject, sender_name, sender_email, updated, added) VALUES (59, 5, 5, 9, 'participant-reply', 'fr_FR', '<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ participant.displayName }} a répondu à votre invitation pour le financement du {{ project.title }}  – {{ project.riskGroupName }}.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
  Nous vous invitons à consulter le dossier en cliquant sur le bouton ci-dessous :
</mj-text>
<mj-button background-color="#F9B13B" border-radius="4px" font-weight="600" inner-padding="7px 30px" href="{{ url("front_projectForm", {projectPublicId : project.publicId}) }}">
    Consulter le dossier
</mj-button>', null, 'KLS - vous avez reçu une réponse !', 'KLS', 'support@kls-platform.com', null, '2020-10-20 18:08:43');
SQL
        );
        $this->addSql(<<<SQL
INSERT INTO mail_template (id, id_header, id_footer, id_layout, name, locale, content, archived, subject, sender_name, sender_email, updated, added) VALUES (60, 5, 5, 9, 'arranger-invitation-external-bank', 'fr_FR', '<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName : "" }},</mj-text>
<mj-text color="#3F2865" font-size="14px" align="justify" line-height="1.5">
{{ arranger.displayName }} vous invite à participer sur la participation de votre établissement, {{ projectParticipation.participant.displayName }}, au financement du dossier ({{ project.title }} – {{ project.riskGroupName }}){% if temporaryToken.token %} sur la plateforme KLS <a href="https://www.kls-platform.com">www.kls-platform.com</a> l’outil d’aide à la syndication {% endif %}.
</mj-text>
<mj-text color="#3F2865" font-size="14px" align="justify" line-height="1.5">
{% if temporaryToken.token %}
Votre compte n’étant pas encore créé sur la plateforme d’aide à la syndication, KLS, nous vous invitons à créer votre compte en cliquant sur le bouton ci-dessous. Vous aurez ensuite accès au dossier.
{% else %}
Nous vous invitons à consulter l’invitation en cliquant sur le bouton ci-dessous.
{% endif %}
</mj-text>
<mj-button 
  background-color="#F9B13B"
  border-radius="4px" 
  font-weight="500"
  inner-padding="7px 30px"
  href="{{ temporaryToken.token ? url("front_initialAccount", {temporaryTokenPublicId : temporaryToken.token, clientPublicId: client.publicId, projectParticipationPublicId: projectParticipation.publicId}) : url("front_viewParticipation", {projectParticipationPublicId: projectParticipation.publicId}) }}"
>
    {% if temporaryToken.token %} Créer mon compte sur KLS {% else %} Consulter l’invitation {% endif %}
</mj-button>', null, 'KLS - vous avez reçu une invitation !', 'KLS', 'support@kls-platform.com', null, '2020-10-20 18:08:43');
SQL
        );
    }
}
