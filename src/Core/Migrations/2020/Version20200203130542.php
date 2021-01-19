<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200203130542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CALS-845 Update change password url';
    }

    public function up(Schema $schema): void
    {
        $sql = <<<'SQL'
        UPDATE mail_template SET content = '
            <mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/reload.png"}) }}"/>
            <mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour {{ client.firstName }},</mj-text>
            <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
                Vous avez demandé à réinitialiser votre mot de passe sur la plateforme KLS.
            </mj-text>
            <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
                Pour cela, cliquez sur le lien ci-dessous. La durée de validité de ce lien est de 24h. Au-delà, merci de bien vouloir reformuler votre demande.
            </mj-text>
            <mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px"
                       href="{{ url("front_password_change", {temporaryTokenHash: temporaryToken.token, clientHash: client.hash}) }}">
                Réinitialiser le mot de passe
            </mj-button>
            <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
                Si vous n’avez pas demandé à réinitialiser votre mot de passe ou pour toute question, merci de contacter le support client KLS (<a href="mailto:support@kls-platform.com">support@kls-platform.com</a>)
            </mj-text>
            <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
        ' WHERE name = 'client-password-request' ;
SQL;
        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {
        $sql = <<<'SQL'
        UPDATE mail_template SET content = '
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
        ' WHERE name = 'client-password-request' ;
SQL;
        $this->addSql($sql);
    }
}
