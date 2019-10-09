<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191009154942 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-368 (Allow user to cancel password reset request)';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $content = <<<'TWIG'
<p>Bonjour {{ firstName }},</p>
<p>Vous avez demandé à réinitialiser votre mot de passe pour le compte {{ email }}.</p>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td class="cta" align="center">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{{ passwordLink }}" style="height:50px;v-text-anchor:middle;width:270px;" arcsize="5%" strokecolor="#2bc9af" fillcolor="#2bc9af">
                <center style="color:#ffffff;font-family:Helvetica, Arial, sans-serif;font-size:18px;">Réinitialiser le mot de passe</center>
            </v:roundrect>
            <![endif]-->
            <a href="{{ passwordLink }}" class="btn-primary">Réinitialiser le mot de passe</a>
        </td>
    </tr>
        <tr>
        <td class="" align="center">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{{ passwordLink }}" style="height:50px;v-text-anchor:middle;width:270px;" arcsize="5%" strokecolor="#2bc9af" fillcolor="#2bc9af">
                <center style="color:#ffffff;font-family:Helvetica, Arial, sans-serif;font-size:18px;">Réinitialiser le mot de passe</center>
            </v:roundrect>
            <![endif]-->
            <a href="{{ cancelPasswordLink }}" class="btn-secondary">Annuler la demande</a>
        </td>
    </tr>
</table>
TWIG;

        $this->addSql(
            "UPDATE mail_template SET content = {$this->connection->quote($content)}, updated = NOW() WHERE name = 'forgotten-password'"
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $content = <<<'TWIG'
<p>Bonjour {{ firstName }},</p>
<p>Vous avez demandé à réinitialiser votre mot de passe pour le compte {{ email }}.</p>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td class="cta" align="center">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{{ passwordLink }}" style="height:50px;v-text-anchor:middle;width:270px;" arcsize="5%" strokecolor="#2bc9af" fillcolor="#2bc9af">
                <center style="color:#ffffff;font-family:Helvetica, Arial, sans-serif;font-size:18px;">Réinitialiser le mot de passe</center>
            </v:roundrect>
            <![endif]-->
            <a href="{{ passwordLink }}" class="btn-primary">Réinitialiser le mot de passe</a>
        </td>
    </tr>
</table>
TWIG;

        $this->addSql(
            "UPDATE mail_template SET content = {$this->connection->quote($content)}, updated = NOW() WHERE name = 'forgotten-password'"
        );
    }
}
