<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200916102943 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'CALS-864 update the wording of the mail templates';
    }

    public function up(Schema $schema) : void
    {
        //publication-prospect-company
        $content = <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à marquer votre intérêt sur la participation de votre Établissement au financement du dossier {{ project.title }} – {{ project.riskGroupName }}, sur la plateforme KLS, l’outil d’aide à la syndication, accessible sur <a href="{{ url("front_home") }}">www.kls-platform.com</a>.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Votre Établissement n’étant pas encore adhérent à la plateforme, nous vous invitons à prendre contact auprès de <a href="mailto:cecile.joly@ca-lendingservices.com">cecile.joly@ca-lendingservices.com</a> ou de <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> afin de pouvoir rapidement formaliser votre adhésion et avoir accès au dossier.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT;
        $this->addSql("UPDATE mail_template SET content = '$content' WHERE name = 'publication-prospect-company'");

        //publication-uninitialized-user
        $content = <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à marquer votre intérêt sur la participation de votre Établissement au financement du dossier {{ project.title }} – {{ project.riskGroupName }}.
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
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT;
        $this->addSql("UPDATE mail_template SET content = '$content' WHERE name = 'publication-uninitialized-user'");

        //publication
        $content = <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à marquer votre intérêt sur la participation de votre Établissement au financement du dossier {{ project.title }} – {{ project.riskGroupName }}.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px"
href="{{ url("front_viewParticipation", {projectParticipationPublicId: projectParticipation.publicId}) }}">
    Consulter l’invitation
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions, l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> ou via le live-chat disponible sur la plateforme.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT;
        $this->addSql("UPDATE mail_template SET content = '$content' WHERE name = 'publication'");

        //syndication-prospect-company
        $content = <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à participer au financement du dossier {{ project.title }} – {{ project.riskGroupName }}, sur la plateforme KLS, l’outil d’aide à la syndication, accessible sur <a href="{{ url("front_home") }}">www.kls-platform.com</a>.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Votre Établissement n’étant pas encore adhérent à la plateforme, nous vous invitons à prendre contact auprès de <a href="mailto:cecile.joly@ca-lendingservices.com">cecile.joly@ca-lendingservices.com</a> ou de <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> afin de pouvoir rapidement formaliser votre adhésion et avoir accès au dossier.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT;
        $this->addSql("UPDATE mail_template SET content = '$content' WHERE name = 'syndication-prospect-company'");

        //syndication-uninitialized-user
        $content = <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à participer au financement sur la participation de votre Établissement au financement du dossier {{ project.title }} – {{ project.riskGroupName }}.
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
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT;
        $this->addSql("UPDATE mail_template SET content = '$content' WHERE name = 'syndication-uninitialized-user'");

        //syndication
        $content = <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à participer au financement du dossier {{ project.title }} – {{ project.riskGroupName }}.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px" href="{{ url("front_viewParticipation", {projectParticipationPublicId : projectParticipation.publicId}) }}">
    Consultez l’invitation
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions, l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> ou via le live-chat disponible sur la plateforme.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT;
        $this->addSql("UPDATE mail_template SET content = '$content' WHERE name = 'syndication'");

        //project-file-uploaded
        $content = <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/attachment-uploaded.png"}) }}"/>
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
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT;
        $this->addSql("UPDATE mail_template SET content = '$content', subject = 'KLS – Nouveau document sur le dossier {{ project.title }} – {{ project.riskGroupName }}' WHERE name = 'project-file-uploaded'");
    }

    public function down(Schema $schema) : void
    {
        //publication-prospect-company
        $content = <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à marquer votre intérêt sur la participation de votre Établissement au financement du dossier {{ project.title }}, sur la plateforme KLS, l’outil d’aide à la syndication, accessible sur <a href="{{ url("front_home") }}">www.kls-platform.com</a>.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Votre Établissement n’étant pas encore adhérent à la plateforme, nous vous invitons à prendre contact auprès de <a href="mailto:cecile.joly@ca-lendingservices.com">cecile.joly@ca-lendingservices.com</a> ou de <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> afin de pouvoir rapidement formaliser votre adhésion et avoir accès au dossier.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT;
        $this->addSql("UPDATE mail_template SET content = '$content' WHERE name = 'publication-prospect-company'");

        //publication-uninitialized-user
        $content = <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à marquer votre intérêt sur la participation de votre Établissement au financement du dossier {{ project.title }}.
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
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT;
        $this->addSql("UPDATE mail_template SET content = '$content' WHERE name = 'publication-uninitialized-user'");

        //publication
        $content = <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à marquer votre intérêt sur la participation de votre Établissement au financement du dossier {{ project.title }}.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px"
href="{{ url("front_viewParticipation", {projectParticipationPublicId: projectParticipation.publicId}) }}">
    Consulter l’invitation
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions, l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> ou via le live-chat disponible sur la plateforme.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT;
        $this->addSql("UPDATE mail_template SET content = '$content' WHERE name = 'publication'");

        //syndication-prospect-company
        $content = <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à participer au financement du dossier {{ project.title }}, sur la plateforme KLS, l’outil d’aide à la syndication, accessible sur <a href="{{ url("front_home") }}">www.kls-platform.com</a>.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Votre Établissement n’étant pas encore adhérent à la plateforme, nous vous invitons à prendre contact auprès de <a href="mailto:cecile.joly@ca-lendingservices.com">cecile.joly@ca-lendingservices.com</a> ou de <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> afin de pouvoir rapidement formaliser votre adhésion et avoir accès au dossier.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT;
        $this->addSql("UPDATE mail_template SET content = '$content' WHERE name = 'syndication-prospect-company'");

        //syndication-uninitialized-user
        $content = <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à participer au financement sur la participation de votre Établissement au financement du dossier {{ project.title }}.
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
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT;
        $this->addSql("UPDATE mail_template SET content = '$content' WHERE name = 'syndication-uninitialized-user'");

        //syndication
        $content = <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à participer au financement du dossier {{ project.title }}.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px" href="{{ url("front_viewParticipation", {projectParticipationPublicId : projectParticipation.publicId}) }}">
    Consultez l’invitation
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions, l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> ou via le live-chat disponible sur la plateforme.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT;
        $this->addSql("UPDATE mail_template SET content = '$content' WHERE name = 'syndication'");

        //project-file-uploaded
        $content = <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/attachment-uploaded.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour {{ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ project.arranger }} vient de charger un nouveau document sur le dossier {{ project.title }} dans la plateforme KLS.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px" href="{{ url("front_viewParticipation", {projectParticipationPublicId: projectParticipation.publicId}) }}">
    Consulter sur KLS
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions, l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a>) ou via le live-chat disponible sur la plateforme.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT;
        $this->addSql("UPDATE mail_template SET content = '$content', subject = 'KLS – Nouveau document sur le dossier {{ project.title }}' WHERE name = 'project-file-uploaded'");
    }
}
