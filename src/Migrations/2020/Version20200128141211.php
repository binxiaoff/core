<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200128141211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-817';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
    INSERT INTO mail_template(id_header, id_footer, id_layout, name, locale, content, subject, sender_name, sender_email, added) 
    VALUES (
        (SELECT id FROM mail_header LIMIT 1),
        (SELECT id FROM mail_footer LIMIT 1),
        (SELECT id FROM mail_layout LIMIT 1),
        'staff-client-initialisation',
        'fr_FR',
        '
         {% set inscriptionFinalisationUrl = url("front_inscription_finalisation", {temporaryTokenHash: temporaryToken.token, clientHash: client.hash }) %}
         <mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "KLS.png"}) }}"/>
         <mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour {{ client.firstName }},</mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify"
                  line-height="1.5">Votre compte, en tant que {{ staff.role }} sur le(s) marché(s) {{ staff.marketSegments|join(", ") }} sur la plateforme KLS, a été créé.</mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
             Nous vous invitons à aller compléter votre profil directement sur <a href="{{ inscriptionFinalisationUrl }}">{{ "kls-platform.com" }}</a>.
         </mj-text>
         
         <mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px"
                    href="{{ inscriptionFinalisationUrl }}">
             Compléter le profil
         </mj-button>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
             Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions,
             l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> ou via l’Intercom disponible sur la plateforme.
         </mj-text>
         <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
',
        'KLS - Initialisation de votre compte',
        'KLS',
        'noreply@kls-platform.com',
        NOW()
    )
SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM mail_template WHERE id = 'staff-client-initialisation'");
    }
}
