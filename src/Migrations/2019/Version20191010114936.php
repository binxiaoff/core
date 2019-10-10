<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Unilend\Migrations\ContainerAwareMigration;
use Unilend\Migrations\Traits\FlushTranslationCacheTrait;

final class Version20191010114936 extends ContainerAwareMigration
{
    use FlushTranslationCacheTrait;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'CALS-368 (Add requester information in password request email)';
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
    {% if requesterData|default([]) %}
    <tr>
        <td class="" align="center">
            <p>Voici quelques informations sur la requête qui nous a été addressée :</p>
            <dl>
                {% for label, data in requesterData %}
                    <dt>{{ ('password-forgotten.' ~ label)|trans }}</dt> <dd>{{ data }}</dd>
                {% endfor %}
            </dl>
        </td>
    </tr>
    {% endif %}
</table>
TWIG;
        $content = $this->connection->quote($content);
        $this->addSql("UPDATE mail_template SET content = {$content}, updated = NOW() WHERE name = 'forgotten-password'");

        $this->addSql("INSERT INTO translations(locale, section, name, translation, added)  VALUES ('fr_FR', 'password-forgotten', 'ip', 'Ip', NOW())");
        $this->addSql("INSERT INTO translations(locale, section, name, translation, added)  VALUES ('fr_FR', 'password-forgotten', 'browser', 'Navigateur', NOW())");
        $this->addSql("INSERT INTO translations(locale, section, name, translation, added)  VALUES ('fr_FR', 'password-forgotten', 'date', 'Date', NOW())");
        $this->addSql("INSERT INTO translations(locale, section, name, translation, added)  VALUES ('fr_FR', 'password-forgotten', 'location', 'Localisation', NOW())");
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

        $content = $this->connection->quote($content);
        $this->addSql("UPDATE mail_template SET content = {$content}, updated = NOW() WHERE name = 'forgotten-password'");

        $this->addSql("DELETE FROM translations WHERE section = 'password-forgotten' AND locale = 'fr_FR' AND name IN ('ip', 'browser', 'date', 'location')");
    }
}
