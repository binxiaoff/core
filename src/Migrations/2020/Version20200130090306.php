<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200130090306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-838 Attachment adding mail';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
    INSERT INTO mail_template(id_header, id_footer, id_layout, name, locale, content, subject, sender_name, sender_email, added) 
    VALUES (
        (SELECT id FROM mail_header LIMIT 1),
        (SELECT id FROM mail_footer LIMIT 1),
        (SELECT id FROM mail_layout LIMIT 1),
        'attachment-uploaded',
        'fr_FR',
        '
            <mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/attachment-uploaded.png"}) }}"/>
            <mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour {{ client.firstName }},</mj-text>
            <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
                {{ project.arranger }} vient de charger un nouveau document sur le dossier {{ project.title }} dans la plateforme KLS.
            </mj-text>
            <mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px"
                       href="{{ url("front_participation_project_view", {projectHash: project.hash}) }}">
                Consulter sur KLS
            </mj-button>
            <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
                Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions, l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a>) ou via l’Intercom disponible sur la plateforme.
            </mj-text>
            <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
        ',
        'KLS – Nouveau document sur le dossier {{ project.title }}',
        'KLS',
        'support@kls-platform.com',
        NOW()
    )
SQL
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
    }
}
