<?php

declare(strict_types=1);

namespace Unilend\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Unilend\Entity\{Interfaces\TwigTemplateInterface, MailFooter, MailHeader, MailLayout, MailTemplate};

class MailFixtures extends AbstractFixtures
{
    /**
     * phpcs:disable
     */
    public const MAILS = [
        [
            'name' => 'staff-client-initialisation',
            'subject' => 'KLS - Initialisation de votre compte',
            'content' => <<<'CONTENT'
{% set inscriptionFinalisationUrl = url("front_initialAccount", {temporaryTokenPublicId: temporaryToken.token, clientPublicId: client.publicId }) %}
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
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT
            ,
        ],
        [
            'name' => 'client-password-request',
            'subject' => 'KLS – votre nouveau mot de passe',
            'content' => <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/reload.png"}) }}"/>
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
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT
            ,
        ],
        [
            'name' => 'publication-prospect-company',
            'subject' => 'KLS - vous avez reçu une demande de marque d’intérêt !',
            'content' => <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à marquer votre intérêt sur la participation de votre établissement, {{ projectParticipation.participant.displayName }}, au financement du dossier {{ project.title }} – {{ project.riskGroupName }}, sur la plateforme KLS, l’outil d’aide à la syndication, accessible sur <a href="{{ url("front_home") }}">www.kls-platform.com</a>.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Votre Établissement n’étant pas encore adhérent à la plateforme, nous vous invitons à prendre contact auprès de <a href="mailto:cecile.joly@ca-lendingservices.com">cecile.joly@ca-lendingservices.com</a> ou de <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> afin de pouvoir rapidement formaliser votre adhésion et avoir accès au dossier.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT
            ,
        ],
        [
            'name' => 'publication-uninitialized-user',
            'subject' => 'KLS - vous avez reçu une demande de marque d’intérêt !',
            'content' => <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
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
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT
            ,
        ],
        [
            'name' => 'publication',
            'subject' => 'KLS - vous avez reçu une demande de marque d’intérêt !',
            'content' => <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
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
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT
            ,
        ],
        [
            'name' => 'syndication-prospect-company',
            'subject' => 'KLS - vous avez reçu une invitation !',
            'content' => <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ arranger.displayName }} vous invite à participer sur la participation de votre établissement, {{ projectParticipation.participant.displayName }}, au financement du dossier {{ project.title }} – {{ project.riskGroupName }}, sur la plateforme KLS, l’outil d’aide à la syndication, accessible sur <a href="{{ url("front_home") }}">www.kls-platform.com</a>.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Votre Établissement n’étant pas encore adhérent à la plateforme, nous vous invitons à prendre contact auprès de <a href="mailto:cecile.joly@ca-lendingservices.com">cecile.joly@ca-lendingservices.com</a> ou de <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> afin de pouvoir rapidement formaliser votre adhésion et avoir accès au dossier.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT
            ,
        ],
        [
            'name' => 'syndication-uninitialized-user',
            'subject' => 'KLS - vous avez reçu une invitation !',
            'content' => <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
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
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT
            ,
        ],
        [
            'name' => 'syndication',
            'subject' => 'KLS - vous avez reçu une invitation !',
            'content' => <<<'CONTENT'
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
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
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
CONTENT
            ,
        ],
        [
            'name' => 'project-file-uploaded',
            'subject' => 'KLS – Nouveau document sur le dossier {{ project.title }} – {{ project.riskGroupName }}',
            'content' => <<<'CONTENT'
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
CONTENT
            ,
        ],
        [
            'name' => 'log',
            'subject' => "{{ level_name }} on KLS {{ environment|default(false) ? 'in ' ~ environment }}",
            'content' => <<<'CONTENT'
<mj-column>
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
  </mj-column>
CONTENT
            ,
        ],
        [
            'name' => 'participant-reply',
            'subject' => 'KLS - vous avez reçu une réponse !',
            'content' => <<<MJML
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ participant.displayName }} a répondu à votre invitation pour le financement du {{ project.title }}  – {{ project.riskGroupName }}.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
  Nous vous invitons à consulter le dossier en cliquant sur le bouton ci-dessous :
</mj-text>
<mj-button background-color="#F9B13B" border-radius="4px" font-weight="600" inner-padding="7px 30px" href="{{ url("front_projectForm", {projectPublicId : project.publicId}) }}">
    Consulter le dossier
</mj-button>
MJML,
        ],
        [
            'name' => 'arranger-invitation-external-bank',
            'content' => <<<CONTENT
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName : "" }},</mj-text>
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
</mj-button>
CONTENT
            ,
            'subject' => 'KLS - vous avez reçu une invitation !'
        ]
    ];

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $footer = $this->createMailFooter();
        $header = $this->createMailHeader();
        $mainLayout = $this->createMailLayout();
        $internalLayout = $this->createInternalLayout();

        foreach (get_defined_vars() as $var) {
            if ($var instanceof TwigTemplateInterface) {
                $manager->persist($var);
            }
        }

        $mails = array_map(static function ($mail) use ($internalLayout, $footer, $header, $mainLayout) {
            return (new MailTemplate($mail['name'], 'log' === $mail['name'] ? $internalLayout : $mainLayout))
                ->setContent($mail['content'])
                ->setSubject($mail['subject'])
                ->setHeader($header)
                ->setSenderEmail('support@kls-platform.com')
                ->setSenderName('KLS')
                ->setFooter($footer);
        }, static::MAILS);

        foreach ($mails as $mail) {
            $manager->persist($mail);
        }

        $manager->flush();
    }

    /**
     * @return MailFooter
     */
    private function createMailFooter(): MailFooter
    {
        $mailFooter = new MailFooter('footer');

        $mailFooter->setContent(<<<CONTENT
  <mj-column>
      <mj-text font-weight="100" font-size="11px" line-height="1.5" align="center" padding-top="40px" color="#3F2865">
          Copyright © 2020 KLS, tous droits réservés.<br/>
          Où nous trouver : 50 rue la Boétie, 75008 Paris, France<br/>
      </mj-text>
  </mj-column>
CONTENT
        );

        return $mailFooter;
    }

    /**
     * @return MailHeader
     */
    private function createMailHeader()
    {
        $header = new MailHeader('header');

        $header->setContent(<<<CONTENT
  <mj-column><mj-image align="left" width="60px" src="{{ url("front_image", {imageFileName: "emails/logo.png"}) }}"/></mj-column>
  <mj-column>
      <mj-text align="right" color="#ffffff" font-size="10px" font-family="Arial" line-height="50px">
          FINANCER MIEUX, FINANCER ENSEMBLE
      </mj-text>
  </mj-column>
CONTENT
        );

        return $header;
    }

    /**
     * @return MailLayout
     */
    private function createMailLayout()
    {
        $layout = new MailLayout('layout');

        $layout->setContent(<<<CONTENT
      <mjml>
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
    </mjml>
CONTENT
        );

        return $layout;
    }

    /**
     * @return MailLayout
     */
    private function createInternalLayout()
    {
        $layout = new MailLayout('internal');

        $layout->setContent(<<<CONTENT
      <mjml>
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
    </mjml>
CONTENT
        );

        return $layout;
    }
}
