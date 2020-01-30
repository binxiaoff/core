<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200129161859 extends AbstractMigration
{
    private const EMAILS = [
        // CALS - 823
        'publication-prospect-company' => [
            'body' => '
         <mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
         <mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
         {{ arranger.name }} vous invite à marquer votre intérêt sur la participation de votre Établissement au financement du dossier {{ project.name }},
          sur la plateforme KLS, l’outil d’aide à la syndication, accessible sur <a href="{{ url("front_home") }}">www.kls-platform.com</a>.
         </mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
             Votre Établissement n’étant pas encore adhérent à la plateforme,
              nous vous invitons à prendre contact auprès de <a href="mailto:cecile.joly@ca-lendingservices.com">cecile.joly@ca-lendingservices.com</a> 
              ou de <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> afin de pouvoir rapidement formaliser votre adhésion et avoir accès au dossier.
         </mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
',
            'subject' => 'KLS - vous avez reçu une demande de marque d’intérêt !',
        ],
        // CALS - 822
        'publication-uninitialized-user' => [
            'body' => '
         <mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
         <mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
         {{ arranger.name }} vous invite à marquer votre intérêt sur la participation de votre Établissement au financement du dossier {{ project.name }}.
         </mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
         Votre compte n’étant pas encore créé sur la plateforme d’aide à la syndication, KLS.
         Nous vous invitons à créer votre compte dès maintenant pour accéder au dossier.
         Votre demande d’habilitation sera transmise à votre responsable pour qu’il valide votre accès.
        </mj-text>
        <mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px" href="">
        Créer mon compte
        </mj-button>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
         Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions,
          l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a>
           ou via l’Intercom disponible sur la plateforme.
        </mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
',
            'subject' => 'KLS - vous avez reçu une demande de marque d’intérêt !',
        ],
        // CALS - 821
        'publication' => [
            'body' => '
         <mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
         <mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
         {{ arranger.name }} vous invite à marquer votre intérêt sur la participation de votre Établissement au financement du dossier {{ project.name }}.
         </mj-text>
         
         <mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px" 
         href="{{ url("front_participation_project_view", {projectHash : project.hash}) }}">
             Consultez l’invitation
         </mj-button>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
            Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions,
            l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a>
            ou via l’Intercom disponible sur la plateforme.
          </mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
',
            'subject' => 'KLS - vous avez reçu une demande de marque d’intérêt !',
        ],
        // CALS - 826
        'syndication-prospect-company' => [
            'body' => '
         <mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
         <mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
         {{ arranger.name }} vous invite participer au financement du dossier {{ project.name }},
          sur la plateforme KLS, l’outil d’aide à la syndication, accessible sur <a href="{{ url("front_home") }}">www.kls-platform.com</a>.
         </mj-text>
         
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
             Votre Établissement n’étant pas encore adhérent à la plateforme,
              nous vous invitons à prendre contact auprès de <a href="mailto:cecile.joly@ca-lendingservices.com">cecile.joly@ca-lendingservices.com</a> 
              ou de <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> afin de pouvoir rapidement formaliser votre adhésion et avoir accès au dossier.
         </mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
',
            'subject' => 'KLS - vous avez reçu une invitation !',
        ],
        // CALS - 825
        'syndication-uninitialized-user' => [
            'body' => '
         <mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
         <mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
         {{ arranger.name }} vous invite participer au financement sur la participation de votre Établissement au financement du dossier {{ project.name }}.
         </mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
         Votre compte n’étant pas encore créé sur la plateforme d’aide à la syndication, KLS.
         Nous vous invitons à créer votre compte dès maintenant pour accéder au dossier.
         Votre demande d’habilitation sera transmise à votre responsable pour qu’il valide votre accès.
        </mj-text>
        <mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px" 
        href="">
        Créer mon compte
        </mj-button>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
         Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions,
          l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a>
           ou via l’Intercom disponible sur la plateforme.
        </mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
',
            'subject' => 'KLS - vous avez reçu une invitation !',
        ],
        // CALS - 824
        'syndication' => [
            'body' => '
         <mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
         <mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
         {{ arranger.name }} vous invite à participer au financement du dossier {{ project.name }}.
         </mj-text>
         <mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px" 
         href="{{ url("front_participation_project_view", {projectHash : project.hash}) }}">
             Consultez l’invitation
         </mj-button>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
            Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions,
            l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a>
            ou via l’Intercom disponible sur la plateforme.
          </mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
',
            'subject' => 'KLS - vous avez reçu une invitation !',
        ],
    ];

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-821 CALS-822 CALS-823 CALS-824 CALS-825 CALS-826 Add project status change invitation';
    }

    public function up(Schema $schema): void
    {
        foreach (static::EMAILS as $name => $email) {
            $this->addSql(
                <<<SQL
    INSERT INTO mail_template(id_header, id_footer, id_layout, name, locale, content, subject, sender_name, sender_email, added) 
    VALUES (
        (SELECT id FROM mail_header LIMIT 1),
        (SELECT id FROM mail_footer LIMIT 1),
        (SELECT id FROM mail_layout LIMIT 1),
        '{$name}',
        'fr_FR',
        '{$email['body']}',
        '{$email['subject']}',
        'KLS',
        'support@kls-platform.com',
        NOW())
SQL
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(static::EMAILS) as $email) {
            $this->addSql("DELETE FROM mail_queue WHERE id_mail_template = (SELECT id FROM mail_template WHERE name = '{$email}' LIMIT 1)");
            $this->addSql("DELETE FROM mail_template WHERE name = '{$email}'");
        }
    }
}
