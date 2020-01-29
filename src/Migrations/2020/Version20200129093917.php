<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200129093917 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-820 Add password reset email';
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
        'client-password-request',
        'fr_FR',
        '
            <mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/reload.png"}) }}"/>
            <mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour {{ client.firstName }},</mj-text>
            <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
                Vous avez demandé à réinitialiser votre mot de passe sur la plateforme KLS.
            </mj-text>
            <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
                Pour cela, cliquez sur le lien ci-dessous. La durée de validité de ce lien est de 24h. Au-delà, merci de bien vouloir reformuler votre demande.
            </mj-text>
            <mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px"
                       href="{{ url("front_password_change", {temporaryTokenHash: temporaryToken.token}) }}">
                Réinitialiser le mot de passe
            </mj-button>
            <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
                Si vous n’avez pas demandé à réinitialiser votre mot de passe ou pour toute question, merci de contacter le support client KLS (<a href="mailto:support@kls-platform.com">support@kls-platform.com</a>)
            </mj-text>
            <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
        ',
        'KLS – votre nouveau mot de passe',
        'KLS',
        'support@kls-platform.com',
        NOW()
    )
SQL
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
